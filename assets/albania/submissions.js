(() => {
  'use strict';
  const form=document.getElementById('investigation-submission');
  if(!form)return;
  const button=document.getElementById('tip-submit'),status=document.getElementById('tip-status'),files=document.getElementById('tip-files'),list=document.getElementById('tip-file-list');
  let busy=false,readyAt=0,tokenTime=0;const t=window.albaniaTranslate||((s)=>s);
  const setStatus=(message,error=false)=>{status.textContent=t(message);status.classList.toggle('tip-error',error);status.setAttribute('role',error?'alert':'status');};
  async function getToken(){
    const response=await fetch(form.dataset.tokenUrl+'&_='+Date.now(),{credentials:'same-origin',cache:'no-store'});
    const json=await response.json();if(!response.ok||!json.success||!json.data.token)throw new Error('Could not prepare the form. Reload this page to try again.');
    form.elements.token.value=json.data.token;tokenTime=Date.now();readyAt=Date.now()+2200;
  }
  getToken().then(()=>{button.disabled=false;setStatus('Ready. Your information will be sent privately for review.');}).catch(()=>setStatus('Could not prepare the form. Reload this page to try again.',true));
  function fileError(){
    if(files.files.length>3)return 'Choose up to 3 files.';
    let total=0;
    for(const file of files.files){total+=file.size;if(!/\.(pdf|txt|jpe?g|png|webp)$/i.test(file.name))return 'Use PDF, TXT, JPG, PNG or WebP files.';if(file.size===0||file.size>5*1024*1024)return 'Each file must be non-empty and no larger than 5 MB.';}
    return total>10*1024*1024?'All attachments together must be no larger than 10 MB.':'';
  }
  files.addEventListener('change',()=>{list.textContent=files.files.length?Array.from(files.files).map(f=>f.name+' ('+(f.size/1024/1024).toFixed(2)+' MB)').join(' · '):'No files selected.';files.setCustomValidity(t(fileError()));});
  form.addEventListener('submit',async event=>{
    event.preventDefault();if(busy)return;
    files.setCustomValidity(t(fileError()));if(!form.reportValidity())return;
    busy=true;button.disabled=true;form.setAttribute('aria-busy','true');setStatus('Uploading and saving your submission. Please keep this page open…');
    try{
      if(Date.now()-tokenTime>7000000)await getToken();
      if(Date.now()<readyAt)await new Promise(resolve=>setTimeout(resolve,readyAt-Date.now()));
      const response=await fetch(form.getAttribute('action'),{method:'POST',body:new FormData(form),credentials:'same-origin'});
      let json;try{json=await response.json();}catch{throw new Error('No receipt was confirmed. Your text is still here; please try again. Retrying will not create a duplicate if it was already saved.');}
      if(!response.ok||!json.success)throw new Error(json.data?.message||'Your submission could not be saved. Please try again.');
      const receipt=json.data.receipt;
      form.reset();list.textContent='No files selected.';
      setStatus(t('Received for private review.')+' '+t('Receipt:')+' '+receipt+'. '+t('Save this code. Your submission is not publicly visible.'));
      status.focus();button.textContent='Submission received ✓';
      // Keep this receipt on screen; reload to start a different submission.
    }catch(error){setStatus(error.message||'Connection failed. Keep your text and try again; your receipt has not been confirmed.',true);status.focus();button.disabled=false;}
    finally{busy=false;form.removeAttribute('aria-busy');}
  });
})();
