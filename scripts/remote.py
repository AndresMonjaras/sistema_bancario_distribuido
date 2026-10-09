import subprocess,json,os,sys,pathlib
root=pathlib.Path(__file__).resolve().parent.parent
conf=json.loads((root/'.runtime/connections.json').read_text())[sys.argv[1]]
env=os.environ.copy();env.update(SSH_ASKPASS=str(root/'.runtime/askpass.sh'),SSH_ASKPASS_REQUIRE='force',BANK_SSH_TARGET=sys.argv[1])
command=sys.argv[2] if len(sys.argv)>2 else 'wsl -l -q'
r=subprocess.run(['ssh','-o','ConnectTimeout=15','-o','StrictHostKeyChecking=accept-new','-o','UserKnownHostsFile=/tmp/bank-known-hosts','-o','PubkeyAuthentication=no','-p',str(conf['port']),conf['user']+'@'+conf['host'],command],env=env,input=(conf['password']+'\n').encode() if command.startswith('sudo -S') else None,capture_output=True,timeout=60)
output=(r.stdout+r.stderr).decode(errors='replace').replace('\x00','')
print(output.replace(conf['password'],'[redacted]'));sys.exit(r.returncode)
