import subprocess,json,os,sys,pathlib,tarfile,shlex
root=pathlib.Path(__file__).resolve().parent.parent;target=sys.argv[1];c=json.loads((root/'.runtime/connections.json').read_text())[target];directory='sucursal' if target=='branch' else 'cajero'
archive=root/'.runtime'/('upload-'+target+'.tar.gz')
with tarfile.open(archive,'w:gz',dereference=True) as tar:
 for p in (root/directory).rglob('*'):
  if p.is_file() and not any(x in p.parts for x in ['node_modules','.git','.runtime','vendor']) and p.name != '.env' and not p.name.endswith(('.sqlite','.log')):tar.add(p,arcname=str(p.relative_to(root/directory)))
 if target=='branch':tar.add(root/'banco-central/vendor',arcname='vendor');tar.add('/tmp/bank-php/usr/lib/php/20230831/pdo_pgsql.so',arcname='.runtime/pdo_pgsql.so')
env=os.environ.copy();env.update(SSH_ASKPASS=str(root/'.runtime/askpass.sh'),SSH_ASKPASS_REQUIRE='force',BANK_SSH_TARGET=target)
cmd="sudo -S -u hadoop bash -c "+shlex.quote('mkdir -p /home/hadoop/sistema-bancario/'+directory+'; tar -xzf - -C /home/hadoop/sistema-bancario/'+directory)
print('Uploading '+directory+' ('+str(archive.stat().st_size//1024)+' KiB)',flush=True)
r=subprocess.run(['ssh','-o','ConnectTimeout=15','-o','StrictHostKeyChecking=accept-new','-o','UserKnownHostsFile=/tmp/bank-known-hosts','-o','PubkeyAuthentication=no','-p',str(c['port']),c['user']+'@'+c['host'],cmd],input=(c['password']+'\n').encode()+archive.read_bytes(),env=env,capture_output=True,timeout=180)
print((r.stdout+r.stderr).decode(errors='replace').replace(c['password'],'[redacted]'));sys.exit(r.returncode)
