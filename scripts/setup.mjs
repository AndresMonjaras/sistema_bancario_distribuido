import fs from 'node:fs';import {randomUUID} from 'node:crypto';
const access=JSON.parse(fs.readFileSync(new URL('../.runtime/access.json',import.meta.url)));
let cookies='',csrf='';
async function call(route,method='GET',body){const r=await fetch('http://127.0.0.1:8100/api/v1/'+route,{method,headers:{'Content-Type':'application/json',Accept:'application/json',Cookie:cookies,'X-CSRF-TOKEN':csrf},...(body?{body:JSON.stringify(body)}:{})});const sets=r.headers.getSetCookie();if(sets.length)cookies=sets.map(s=>s.split(';')[0]).join('; ');const text=await r.text();let d;try{d=JSON.parse(text);}catch{throw new Error('Invalid JSON: '+text.slice(0,250));}if(!r.ok)throw new Error(JSON.stringify({status:r.status,data:d}));if(d.data?.csrf_token)csrf=d.data.csrf_token;return d.data;}
await call('csrf');await call('auth/login','POST',{email:access.central.email,password:access.central.password});
const nodePath=new URL('../.runtime/nodes.json',import.meta.url);const nodes=fs.existsSync(nodePath)?JSON.parse(fs.readFileSync(nodePath)):{};
for(const [key,type,name,responsible,cash] of [['branch','branch','Sucursal Centro','Onavi','5000.00'],['atm','atm','Cajero Principal','Dennis','2000.00']]){
 if(!nodes[key])nodes[key]=await call('admin/nodes','POST',{type,name,responsible,initial_cash:cash});
 const file=new URL('../'+(key==='branch'?'sucursal':'cajero')+'/.env',import.meta.url);let s=fs.readFileSync(file,'utf8');s=s.replace(/^NODE_API_KEY=.*$/m,'NODE_API_KEY='+nodes[key].api_key);fs.writeFileSync(file,s,{mode:0o600});console.log('Created/configured',key,nodes[key].id);
 fs.writeFileSync(nodePath,JSON.stringify(nodes),{mode:0o600});
}
