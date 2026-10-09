import subprocess,json,os,sys,pathlib
root=pathlib.Path(__file__).resolve().parent.parent;c=json.loads((root/'.runtime/connections.json').read_text())[sys.argv[1]]
env=os.environ.copy();env.update(SSH_ASKPASS=str(root/'.runtime/askpass.sh'),SSH_ASKPASS_REQUIRE='force',BANK_SSH_TARGET=sys.argv[1])
local_port='8001' if sys.argv[1]=='branch' else '3000'
print('Establishing service connectivity for '+sys.argv[1],flush=True)
subprocess.run(['ssh','-N','-o','ServerAliveInterval=20','-o','ServerAliveCountMax=3','-o','ExitOnForwardFailure=yes','-o','ConnectTimeout=15','-o','StrictHostKeyChecking=accept-new','-o','UserKnownHostsFile=/tmp/bank-known-hosts','-o','PubkeyAuthentication=no','-p',str(c['port']),'-R','18000:127.0.0.1:8100','-L',local_port+':127.0.0.1:'+local_port,c['user']+'@'+c['host']],env=env,stdin=subprocess.DEVNULL)
