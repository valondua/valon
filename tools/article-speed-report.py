#!/usr/bin/env python3
"""Summarize saved, per-URL PageSpeed lab runs without fetching or changing them.

Usage: python3 tools/article-speed-report.py INPUT_ROOT OUTPUT_DIRECTORY
Add --require-complete to require valid after/mobile and after/desktop reports
for every inventory entry. Draft files are still written when that check fails.
"""

import argparse
import csv
import html
import json
import math
import statistics
import sys
from collections import Counter
from dataclasses import asdict, dataclass
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import urlsplit


PHASES = ("before", "after")
DEVICES = ("mobile", "desktop")
METRICS = {"lcp_s": ("largest-contentful-paint", 1000),
           "tbt_ms": ("total-blocking-time", 1),
           "cls": ("cumulative-layout-shift", 1)}
ABOUT_PSI = "https://developers.google.com/speed/docs/insights/v5/about"


@dataclass
class Report:
    status: str
    error: str = ""
    fetch_time: str = ""
    final_url: str = ""
    lighthouse_version: str = ""
    score: float | None = None
    lcp_s: float | None = None
    tbt_ms: float | None = None
    cls: float | None = None
    warnings: str = ""
    source_file: str = ""


def number(value):
    try:
        return isinstance(value, (int, float)) and not isinstance(value, bool) and math.isfinite(value)
    except OverflowError:
        return False


def timestamp(value):
    if not isinstance(value, str) or not value:
        raise ValueError("missing fetchTime")
    parsed = datetime.fromisoformat(value.replace("Z", "+00:00"))
    if parsed.tzinfo is None:
        raise ValueError("fetchTime has no timezone")
    return parsed


def load_inventory(root):
    data = json.loads((root / "inventory.json").read_text(encoding="utf-8"))
    articles = data.get("articles")
    if not isinstance(articles, list) or not articles:
        raise ValueError("inventory must contain a nonempty articles array")
    if "count" in data and data["count"] != len(articles):
        raise ValueError("inventory count differs from its articles array")
    ids, urls = set(), set()
    for article in articles:
        ident, url = article.get("id"), article.get("url")
        if not isinstance(ident, int) or isinstance(ident, bool) or ident <= 0 or ident in ids:
            raise ValueError("inventory article IDs must be unique positive integers")
        if not isinstance(url, str) or urlsplit(url).scheme not in ("https", "http") or not urlsplit(url).netloc or url in urls:
            raise ValueError(f"invalid or duplicate inventory URL for article {ident}")
        if not isinstance(article.get("title"), str) or not isinstance(article.get("language"), str) or not isinstance(article.get("video"), bool):
            raise ValueError(f"missing title, language or video type for article {ident}")
        ids.add(ident)
        urls.add(url)
    return articles


def read_report(root, article, phase, device):
    relative = Path(phase) / device / f'{article["id"]}.json'
    path = root / relative
    result = Report("missing", source_file=relative.as_posix())
    if not path.exists():
        failure = path.with_suffix(".error.json")
        if failure.exists():
            result.status = "failed"
            try:
                data = json.loads(failure.read_text(encoding="utf-8"))
                result.error = str(data.get("error", "measurement failed"))[:500]
            except (OSError, ValueError, AttributeError) as error:
                result.error = f"unreadable failure record: {error}"
            result.source_file = failure.relative_to(root).as_posix()
        else:
            result.error = "report file not present in this snapshot"
        return result
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
        if not isinstance(data, dict):
            raise ValueError("report is not a JSON object")
        for key, attribute in (("fetchTime", "fetch_time"), ("finalDisplayedUrl", "final_url"), ("lighthouseVersion", "lighthouse_version")):
            if isinstance(data.get(key), str):
                setattr(result, attribute, data[key])
        result.warnings = json.dumps(data.get("runWarnings") or [], ensure_ascii=False)
        if data.get("runtimeError") is not None or data.get("error") is not None:
            result.status = "failed"
            error = data["runtimeError"] if data.get("runtimeError") is not None else data["error"]
            result.error = json.dumps(error, ensure_ascii=False)[:500]
            return result
        if data.get("requestedUrl") != article["url"]:
            raise ValueError("requestedUrl does not exactly match inventory URL")
        if data.get("strategy") != device:
            raise ValueError("strategy does not match report directory")
        audit = data.get("_audit") or {}
        if not isinstance(audit, dict):
            raise ValueError("_audit metadata is not an object")
        for key, expected in (("postId", article["id"]), ("strategy", device), ("phase", phase)):
            if key in audit and audit[key] != expected:
                raise ValueError(f"_audit.{key} does not match inventory/report directory")
        timestamp(data.get("fetchTime"))
        score = data.get("performanceScore")
        if not number(score) or not 0 <= score <= 1:
            raise ValueError("performanceScore must be a finite number from 0 to 1")
        values = {}
        audits = data.get("audits")
        if not isinstance(audits, dict):
            raise ValueError("missing Lighthouse audits")
        for field, (audit_id, divisor) in METRICS.items():
            metric = audits.get(audit_id)
            value = metric.get("numericValue") if isinstance(metric, dict) else None
            if not number(value) or value < 0:
                raise ValueError(f"missing or invalid {audit_id}.numericValue")
            if metric.get("errorMessage") or metric.get("scoreDisplayMode") == "error":
                raise ValueError(f"Lighthouse reported an error for {audit_id}")
            expected_unit = "unitless" if field == "cls" else "millisecond"
            if metric.get("numericUnit", expected_unit) != expected_unit:
                raise ValueError(f"unexpected unit for {audit_id}")
            values[field] = value / divisor
        result.status = "valid"
        result.score = round(score * 100, 10)
        for field, value in values.items():
            setattr(result, field, value)
    except (OSError, ValueError, TypeError, AttributeError) as error:
        result.status = "invalid"
        result.error = str(error)[:500]
    return result


def display(value, digits=2):
    if value is None:
        return "—"
    return "0" if round(value, digits) == 0 else f"{value:.{digits}f}".rstrip("0").rstrip(".")


def median(reports, field):
    values = [getattr(report, field) for report in reports]
    return statistics.median(values) if values else None


def cell(value):
    return str(value).replace("\n", " ").replace("\r", " ").replace("|", "\\|").replace("[", "\\[").replace("]", "\\]")


def comparison(before, after):
    result = {"comparison_status": "unavailable", "regression_flags": ""}
    result.update({f"{field}_delta": None for field in ("score", "lcp_s", "tbt_ms", "cls")})
    if before.status != "valid" or after.status != "valid":
        return result
    if timestamp(after.fetch_time) <= timestamp(before.fetch_time):
        result["comparison_status"] = "after_not_later"
        return result
    result["comparison_status"] = "paired"
    for field in ("score", "lcp_s", "tbt_ms", "cls"):
        result[f"{field}_delta"] = round(getattr(after, field) - getattr(before, field), 10)
    flags = []
    if result["score_delta"] <= -5:
        flags.append("score drop >=5 points")
    if result["lcp_s_delta"] >= .5 and after.lcp_s >= before.lcp_s * 1.2:
        flags.append("LCP rise >=0.5s and 20%")
    if result["tbt_ms_delta"] >= 100 and after.tbt_ms >= before.tbt_ms * 1.2:
        flags.append("TBT rise >=100ms and 20%")
    if result["cls_delta"] >= .05:
        flags.append("CLS rise >=0.05")
    result["regression_flags"] = "; ".join(flags)
    return result


def build_summary(root, articles, reports, generated):
    total = len(articles)
    final_valid = sum(reports[(a["id"], "after", d)].status == "valid" for a in articles for d in DEVICES)
    state = "Final-run coverage complete" if final_valid == total * 2 else "Draft — final-run coverage incomplete"
    languages = ", ".join(f"{name}: {count}" for name, count in sorted(Counter(a["language"] for a in articles).items()))
    lines = ["# Article page-speed audit", "", f"**{state}: {final_valid}/{total * 2} valid after reports.**",
             f"Snapshot generated: {generated}. Inventory: {total} published URLs ({languages}); "
             f'{sum(not a["video"] for a in articles)} ordinary articles and {sum(a["video"] for a in articles)} video articles.', "",
             "These are Lighthouse lab measurements, not real-user CrUX results or SEO rankings. "
             "Runs can vary; a score of 90+ does not establish good field performance. "
             f"[Google: About PageSpeed Insights]({ABOUT_PSI}).", "",
             "The CSV contains every inventory URL, exact fetch timestamps, report status and source filename. "
             "Blank metric cells mean unavailable or rejected data; they never mean zero. "
             "Only valid reports enter the following counts and medians. Each aggregate uses its own available sample.", "",
             "| Run | Valid / expected | Missing | Failed | Invalid | Score ≥90 | Median score | Median LCP (s) | Median TBT (ms) | Median CLS |",
             "|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|"]
    for phase in PHASES:
        for device in DEVICES:
            group = [reports[(a["id"], phase, device)] for a in articles]
            counts = Counter(r.status for r in group)
            valid = [r for r in group if r.status == "valid"]
            good = f"{sum(r.score >= 90 for r in valid)}/{len(valid)}" if valid else "—"
            metrics = [display(median(valid, f), 3 if f == "cls" else 2) for f in ("score", "lcp_s", "tbt_ms", "cls")]
            lines.append(f'| {phase} {device} | {len(valid)}/{total} | {counts["missing"]} | {counts["failed"]} | {counts["invalid"]} | '
                         f'{good} | ' + " | ".join(metrics) + " |")
    lines += ["", "## Matched before/after changes", "",
              "Only URLs with valid runs in both phases and a later after timestamp are paired. Deltas are after minus before; negative LCP/TBT/CLS is better.", "",
              "| Device | Paired URLs | Median score delta | Median LCP delta (s) | Median TBT delta (ms) | Median CLS delta |",
              "|---|---:|---:|---:|---:|---:|"]
    chronology = 0
    for device in DEVICES:
        pairs = [(reports[(a["id"], "before", device)], reports[(a["id"], "after", device)]) for a in articles]
        chronology += sum(comparison(before, after)["comparison_status"] == "after_not_later" for before, after in pairs)
        pairs = [(before, after) for before, after in pairs if comparison(before, after)["comparison_status"] == "paired"]
        deltas = [statistics.median(getattr(after, f) - getattr(before, f) for before, after in pairs) if pairs else None
                  for f in ("score", "lcp_s", "tbt_ms", "cls")]
        lines.append(f"| {device} | {len(pairs)} | " + " | ".join(display(v, 3 if i == 3 else 2) for i, v in enumerate(deltas)) + " |")
    if chronology:
        lines += ["", f"**Timestamp warning:** {chronology} after reports are not later than their before reports and were excluded from comparisons."]
    flagged = []
    for article in articles:
        for device in DEVICES:
            before, after = (reports[(article["id"], phase, device)] for phase in PHASES)
            change = comparison(before, after)
            if change["regression_flags"]:
                flagged.append((article, device, before, after, change))
    flagged.sort(key=lambda row: (-row[4]["lcp_s_delta"], row[4]["score_delta"], row[0]["url"]))
    counts = Counter(row[1] for row in flagged)
    lines += ["", "## Potential regressions to recheck", "",
              f'{len(flagged)} paired runs flagged ({counts["mobile"]} mobile, {counts["desktop"]} desktop). '
              "Review thresholds: score drop ≥5 points; LCP rise ≥0.5s and ≥20%; TBT rise ≥100ms and ≥20%; or CLS rise ≥0.05. "
              "These are retest candidates from individual lab runs, not confirmed causal regressions. All deltas and flags are in the CSV."]
    if flagged:
        lines += ["", "Largest LCP increases among flagged runs (up to 10):", "",
                  "| Page | Device | Score before → after | LCP before → after (s) | TBT delta (ms) | CLS delta | Review flags |",
                  "|---|---|---:|---:|---:|---:|---|"]
        for article, device, before, after, change in flagged[:10]:
            lines.append(f'| [{cell(html.unescape(article["title"]))}](<{article["url"]}>) | {device} | '
                         f'{display(before.score)} → {display(after.score)} | {display(before.lcp_s)} → {display(after.lcp_s)} | '
                         f'{display(change["tbt_ms_delta"])} | {display(change["cls_delta"], 3)} | {cell(change["regression_flags"])} |')
    lines += ["", "## Slowest remaining measured mobile pages", "",
              "Ranked by after-run LCP, highest first, then lower performance score. Missing or rejected final runs are not ranked."]
    worst = [(a, reports[(a["id"], "after", "mobile")]) for a in articles if reports[(a["id"], "after", "mobile")].status == "valid"]
    worst.sort(key=lambda pair: (-pair[1].lcp_s, pair[1].score, pair[0]["url"]))
    if worst:
        lines += ["", "| Page | Language / type | Score | LCP (s) | TBT (ms) | CLS | Exact fetch time |",
                  "|---|---|---:|---:|---:|---:|---|"]
        for article, report in worst[:10]:
            title = cell(html.unescape(article["title"]))
            kind = "video" if article["video"] else "ordinary"
            lines.append(f'| [{title}](<{article["url"]}>) | {cell(article["language"])} / {kind} | {display(report.score)} | '
                         f'{display(report.lcp_s)} | {display(report.tbt_ms)} | {display(report.cls, 3)} | {cell(report.fetch_time)} |')
    else:
        lines += ["", "No valid after/mobile reports are available yet; baseline results are not substituted."]
    lines += ["", "## Evidence notes", "", f"Input root: `{root}`. One CSV row represents one published URL, including each language separately.", ""]
    for phase in PHASES:
        for device in DEVICES:
            valid = [reports[(a["id"], phase, device)] for a in articles if reports[(a["id"], phase, device)].status == "valid"]
            if valid:
                ordered = sorted(valid, key=lambda r: timestamp(r.fetch_time))
                versions = ", ".join(sorted({r.lighthouse_version or "unreported" for r in valid}))
                lines.append(f"- {phase} {device}: fetch range `{ordered[0].fetch_time}`–`{ordered[-1].fetch_time}`; Lighthouse {versions}.")
    invalid_reasons = Counter(r.error for r in reports.values() if r.status in ("failed", "invalid"))
    if invalid_reasons:
        lines += ["", "Rejected-report reasons (full per-page status remains in the CSV):"]
        for reason, count in invalid_reasons.most_common(8):
            lines.append(f"- {count}: {cell(reason)}")
    redirects = sum(r.status == "valid" and bool(r.final_url) and r.final_url != a["url"]
                    for a in articles for p in PHASES for d in DEVICES for r in [reports[(a["id"], p, d)]])
    if redirects:
        lines += ["", f"{redirects} valid reports have a different final displayed URL; inspect the final_url columns for redirects."]
    return "\n".join(lines) + "\n", final_valid


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("input_root", type=Path)
    parser.add_argument("output_directory", type=Path)
    parser.add_argument("--require-complete", action="store_true", help="exit 2 unless all inventory URLs have valid after/mobile and after/desktop reports")
    args = parser.parse_args(argv)
    root, destination = args.input_root.resolve(), args.output_directory.resolve()
    try:
        articles = load_inventory(root)
        reports = {(a["id"], phase, device): read_report(root, a, phase, device)
                   for a in articles for phase in PHASES for device in DEVICES}
        generated = datetime.now(timezone.utc).isoformat(timespec="seconds")
        summary, final_valid = build_summary(root, articles, reports, generated)
        destination.mkdir(parents=True, exist_ok=True)
        columns = ["id", "url", "title", "language", "type"]
        columns += [f"{phase}_{device}_{field}" for phase in PHASES for device in DEVICES for field in Report.__dataclass_fields__]
        columns += [f"{device}_{field}" for device in DEVICES for field in comparison(Report("missing"), Report("missing"))]
        with (destination / "article-page-speed.csv").open("w", encoding="utf-8", newline="") as stream:
            writer = csv.DictWriter(stream, fieldnames=columns)
            writer.writeheader()
            for article in articles:
                row = {k: article[k] for k in ("id", "url", "language")}
                row.update(title=html.unescape(article["title"]), type="video" if article["video"] else "ordinary")
                for phase in PHASES:
                    for device in DEVICES:
                        row.update({f"{phase}_{device}_{key}": value for key, value in asdict(reports[(article["id"], phase, device)]).items()})
                for device in DEVICES:
                    change = comparison(reports[(article["id"], "before", device)], reports[(article["id"], "after", device)])
                    row.update({f"{device}_{key}": value for key, value in change.items()})
                writer.writerow(row)
        (destination / "summary.md").write_text(summary, encoding="utf-8")
        print(f"Wrote {len(articles)} article rows to {destination}; valid final reports: {final_valid}/{len(articles) * 2}.")
        if args.require_complete and final_valid != len(articles) * 2:
            print("Incomplete final coverage; draft outputs were written with missing/failed/invalid reports marked.", file=sys.stderr)
            return 2
        return 0
    except (OSError, ValueError, TypeError, AttributeError) as error:
        print(f"Report generation failed: {error}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
