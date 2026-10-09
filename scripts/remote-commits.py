import subprocess,json,os,sys,pathlib,tarfile,shlex
root=pathlib.Path(__file__).resolve().parent.parent;target=sys.argv[1];c=json.loads((root/'.runtime/connections.json').read_text())[target];module='sucursal' if target=='branch' else 'cajero';bundle=root/'.runtime'/('commit-'+target+'.tar.gz')
with tarfile.open(bundle,'w:gz')as tar:
 tar.add(root/'.runtime/central.bundle',arcname='central.bundle')
 for p in (root/module).rglob('*'):
  if p.is_file() and p.name!='.env' and not any(x in p.parts for x in ['vendor','node_modules','.runtime','storage','.git']) and not p.name.endswith('.sqlite'):tar.add(p,arcname='source/'+module+'/'+str(p.relative_to(root/module)))
env=os.environ.copy();env.update(SSH_ASKPASS=str(root/'.runtime/askpass.sh'),SSH_ASKPASS_REQUIRE='force',BANK_SSH_TARGET=target)
base='/home/'+c['user']+'/examen-banco-git';repo=base+'/repo';branch='nodo-'+module
cmd='mkdir -p '+base+' && tar -xzf - -C '+base+' && git clone -b main '+base+'/central.bundle '+repo+' && cp -a '+base+'/source/'+module+' '+repo+'/ && cd '+repo+' && git remote set-url origin https://github.com/AndresMonjaras/sistema_bancario_distribuido.git && git checkout -b '+branch+' && git add '+module+' && git commit -m '+shlex.quote(('Omar: implementar sucursal Laravel y persistencia operativa' if target=='branch' else 'Dennis: implementar ATM Express, sesiones Supabase y Vercel'))+' && git bundle create '+base+'/'+module+'.bundle '+branch+' && git log -1 --format="%h %an <%ae> %s"'
r=subprocess.run(['ssh','-o','ConnectTimeout=15','-o','StrictHostKeyChecking=accept-new','-o','UserKnownHostsFile=/tmp/bank-known-hosts','-o','PubkeyAuthentication=no','-p',str(c['port']),c['user']+'@'+c['host'],cmd],input=bundle.read_bytes(),env=env,capture_output=True,timeout=150)
print((r.stdout+r.stderr).decode(errors='replace'));sys.exit(r.returncode)
