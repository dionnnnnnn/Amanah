const {chromium}=require('C:/Users/diplk/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs=require('fs'); const path=require('path');
const root='C:/Users/diplk/Documents/amanah'; const out=__dirname;
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true});
 const context=await browser.newContext({viewport:{width:1440,height:1000}});
 await context.route('**/*',route=>route.request().url().startsWith('http://127.0.0.1:8765/')?route.continue():route.abort());
 const page=await context.newPage(); const errors=[]; page.on('pageerror',e=>errors.push(e.message));
 const files=fs.readdirSync(root).filter(f=>f.endsWith('.html')&&f!=='Amanah.html'); const results=[];
 for(const file of files){
   for(const width of [320,375,768,980,1024,1280,1440]){
    await page.setViewportSize({width,height:1000}); const res=await page.goto('http://127.0.0.1:8765/'+file);
    results.push(await page.evaluate(({file,width,status})=>({file,width,status,title:document.title,h1:document.querySelectorAll('h1').length,scrollWidth:document.documentElement.scrollWidth,viewport:innerWidth,overflow:[...document.querySelectorAll('body *')].filter(e=>{const r=e.getBoundingClientRect();return r.width>0&&(r.right>innerWidth+1||r.left< -1)&&getComputedStyle(e).position!=='absolute'&&getComputedStyle(e).position!=='fixed'}).slice(0,8).map(e=>({tag:e.tagName,cls:e.className,text:e.textContent.slice(0,45)}))}),{file,width,status:res.status()}));
   }
 }
 await page.setViewportSize({width:1440,height:1000}); await page.goto('http://127.0.0.1:8765/index.html'); await page.screenshot({path:path.join(out,'accueil-desktop.png'),fullPage:true});
 await page.setViewportSize({width:375,height:900}); await page.goto('http://127.0.0.1:8765/index.html'); await page.screenshot({path:path.join(out,'accueil-mobile.png'),fullPage:true});
 await page.locator('[data-menu-button]').click(); const menuOpened=await page.locator('[data-mobile-nav]').isVisible(); await page.keyboard.press('Escape'); const menuClosed=!(await page.locator('[data-mobile-nav]').isVisible()); const focusRestored=await page.locator('[data-menu-button]').evaluate(e=>e===document.activeElement);
 await page.locator('details').first().locator('summary').click(); const faqOpened=await page.locator('details').first().getAttribute('open');
 await page.setViewportSize({width:1280,height:1000}); await page.goto('http://127.0.0.1:8765/faire-un-don.html'); const requests=[];page.on('request',r=>requests.push({method:r.method(),url:r.url()}));
 const amounts=[];
 for(const value of ['1.10','4.10','10.01','150.50','0.50','0','-1','1.001','1000000000','']){
  await page.locator('[data-custom-amount]').fill(value); await page.getByRole('button',{name:'Vérifier mon choix'}).click(); amounts.push({value,status:await page.locator('[data-donation-status]').innerText(),nativeValidity:await page.locator('[data-custom-amount]').evaluate(e=>e.validity.valid),summary:await page.locator('[data-summary-amount]').innerText()});
 }
 await page.screenshot({path:path.join(out,'don-desktop.png'),fullPage:true});
 const darkText=await page.locator('.don-summary .eyebrow').evaluate(e=>({text:e.textContent,color:getComputedStyle(e).color,background:getComputedStyle(e.parentElement).backgroundColor}));
 await page.goto('http://127.0.0.1:8765/faire-un-don.html?montant=80'); const queryState=await page.evaluate(()=>({custom:document.querySelector('[data-custom-amount]').value,checked:[...document.querySelectorAll("input[name='amount-preset']:checked")].map(e=>e.value),summary:document.querySelector('[data-summary-amount]').textContent}));
 await page.goto('http://127.0.0.1:8765/contact.html'); await page.locator('#name').fill('Audit fictif');await page.locator('#email').fill('audit@example.invalid');await page.locator('#subject').selectOption({label:'Demande générale'});await page.locator('#message').fill('Message fictif pour audit local.');await page.locator('input[type=checkbox]').check(); const beforeContact=requests.length;await page.getByRole('button',{name:'Vérifier mon message'}).click();const contact={status:await page.locator('[data-form-status]').innerText(),requestsAfterSubmit:requests.slice(beforeContact)};
 const nojs=await browser.newContext({javaScriptEnabled:false}); const p2=await nojs.newPage(); await p2.goto('http://127.0.0.1:8765/contact.html');await p2.locator('#name').fill('Audit fictif');await p2.locator('#email').fill('audit@example.invalid');await p2.locator('#subject').selectOption({label:'Demande générale'});await p2.locator('#message').fill('Message fictif audit sans JavaScript.');await p2.locator('input[type=checkbox]').check();await Promise.all([p2.waitForURL('**/contact.html?**'),p2.getByRole('button',{name:'Vérifier mon message'}).click()]);const nojsContactUrl=p2.url();
 fs.writeFileSync(path.join(out,'frontend-results.json'),JSON.stringify({browser:browser.version(),files,checks:results,errors,menuOpened,menuClosed,focusRestored,faqOpened,amounts,darkText,queryState,contact,nojsContactUrl,donationSubmitNetwork:requests.filter(r=>r.method!=='GET')},null,2));
 console.log(JSON.stringify({browser:browser.version(),pages:files.length,viewportChecks:results.length,overflow:results.filter(r=>r.scrollWidth>r.viewport),errors,menuOpened,menuClosed,focusRestored,faqOpened,amounts,darkText,queryState,contact,nojsContactUrl,output:path.join(out,'frontend-results.json')},null,2));
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
