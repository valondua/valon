const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs/promises');
const os = require('node:os');
const path = require('node:path');
const { SOURCES, assertMetadata, generate } = require('./generate-animated-images.cjs');

test('changed original fails the build and preserves the previously published manifest', async () => {
  const directory = await fs.mkdtemp(path.join(os.tmpdir(), 'valon-animation-generator-'));
  try {
    const outputDir = path.join(directory, 'assets');
    const sourceDir = path.join(directory, 'sources');
    await fs.mkdir(outputDir);
    await fs.mkdir(sourceDir);
    const previous = '{"previous":"reviewed manifest must remain byte-identical"}\n';
    await fs.writeFile(path.join(outputDir, 'optimized-animations.json'), previous);
    await fs.writeFile(path.join(sourceDir, SOURCES[0].id + '.gif'), 'changed original contents');
    await assert.rejects(generate({ reportDir: directory, sourceDir, outputDir, sources: [SOURCES[0]] }), /prior animation manifest preserved/);
    assert.equal(await fs.readFile(path.join(outputDir, 'optimized-animations.json'), 'utf8'), previous);
    const report = JSON.parse(await fs.readFile(path.join(directory, 'generation-report.json'), 'utf8'));
    assert.equal(report.sources[0].status, 'failed');
    assert.match(report.sources[0].reason, /SHA-256 changed/);
    assert.deepEqual(await fs.readdir(outputDir), ['optimized-animations.json']);
  } finally {
    await fs.rm(directory, { recursive: true, force: true });
  }
});

test('any changed frame count, individual timing, dimensions or loop is rejected', () => {
  const expected = SOURCES[0];
  for (const key of ['width', 'height', 'frames', 'loop']) {
    assert.throws(() => assertMetadata({ ...expected, [key]: expected[key] + 1 }, expected), new RegExp(key + ' changed'));
  }
  const delay = [...expected.delay];
  delay[delay.length - 1] += 10;
  assert.throws(() => assertMetadata({ ...expected, delay }, expected), /delay changed/);
});
