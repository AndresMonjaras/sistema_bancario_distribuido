import subprocess,json,os,sys,pathlib
root=pathlib.Path(__file__).resolve().parent.parent;t=sys.argv[1];c=json.loads((root/'.runtime/connections.json').read_text())[t];m='sucursal' if t=='branch' else 'cajero';env=os.environ.copy();env.update(SSH_ASKPASS=str(root/'.runtime/askpass.sh'),SSH_ASKPASS_REQUIRE='force',BANK_SSH_TARGET=t)
r=subprocess.run(['ssh','-o','ConnectTimeout=15','-o','StrictHostKeyChecking=accept-new','-o','UserKnownHostsFile=/tmp/bank-known-hosts','-o','PubkeyAuthentication=no','-p',str(c['port']),c['user']+'@'+c['host'],'cat /home/'+c['user']+'/examen-banco-git/'+m+'.bundle'],env=env,capture_output=True,timeout=90)
if r.returncode:print(r.stderr.decode(errors='replace'));sys.exit(r.returncode)
(root/'.runtime'/ (m+'.bundle')).write_bytes(r.stdout);print('Recibido historial Git creado por '+c['user']+' para '+m)
