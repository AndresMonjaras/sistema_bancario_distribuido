import pg from 'pg';import fs from 'node:fs';
const config=JSON.parse(fs.readFileSync(new URL('../.runtime/connections.json',import.meta.url)));
const regions=['us-east-1','us-east-2','us-west-1','us-west-2','sa-east-1','eu-west-1','eu-central-1','ap-southeast-1','ap-northeast-1'];
for(const name of ['central_db','nodes_db']){
 const c=config[name];const endpoints=[{host:`db.${c.ref}.supabase.co`,user:'postgres'},...regions.flatMap(r=>[0,1].map(n=>({host:`aws-${n}-${r}.pooler.supabase.com`,user:`postgres.${c.ref}`})))];
 let found;
 await Promise.all(endpoints.map(async e=>{const client=new pg.Client({...e,password:c.password,database:'postgres',port:5432,ssl:{rejectUnauthorized:false},connectionTimeoutMillis:4500});try{await client.connect();await client.query('select 1');found=e;console.log(name,'connected',e.host);await client.end();}catch(err){console.log(name,e.host,err.code||err.message.split('\n')[0]);await client.end().catch(()=>{});}}));
 if(found){config[name]={...c,...found,port:5432};fs.writeFileSync(new URL('../.runtime/connections.json',import.meta.url),JSON.stringify(config));}
}
