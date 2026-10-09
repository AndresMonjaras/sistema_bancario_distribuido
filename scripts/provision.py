import subprocess,json,os,sys,pathlib,shlex
root=pathlib.Path(__file__).resolve().parent.parent;target=sys.argv[1];c=json.loads((root/'.runtime/connections.json').read_text())[target];directory='sucursal' if target=='branch' else 'cajero'
env=os.environ.copy();env.update(SSH_ASKPASS=str(root/'.runtime/askpass.sh'),SSH_ASKPASS_REQUIRE='force',BANK_SSH_TARGET=target)
contents=(root/directory/'.env').read_bytes()
assert b'postgres.kxlrredguehuujcmxcpt' not in contents, 'Central credentials cannot leave this machine'
cmd='sudo -S -u hadoop bash -c '+shlex.quote('umask 077; cat > /home/hadoop/sistema-bancario/'+directory+'/.env')
r=subprocess.run(['ssh','-o','ConnectTimeout=15','-o','StrictHostKeyChecking=accept-new','-o','UserKnownHostsFile=/tmp/bank-known-hosts','-o','PubkeyAuthentication=no','-p',str(c['port']),c['user']+'@'+c['host'],cmd],input=(c['password']+'\n').encode()+contents,env=env,capture_output=True,timeout=60)
print((r.stdout+r.stderr).decode(errors='replace').replace(c['password'],'[redacted]'));print('Configuration provisioned for '+target if r.returncode==0 else 'Provisioning failed');sys.exit(r.returncode)
