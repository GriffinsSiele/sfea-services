import os
from pathlib import Path
tinc_path=Path('/etc/tinc/i-sphere')

def get_servers():
    keys_list=os.path.join(tinc_path,'hosts')
    keys=[]
    for file in os.listdir(keys_list):
        if '.' in file:
            continue
        with open(os.path.join(keys_list,file)) as f:
            if 'ddress' in f.read():
                keys.append(file)
    return keys

def read_conf():
    conf=''
    with open(os.path.join(tinc_path,'tinc.conf')) as f:
        for line in f.readlines():
            if 'ConnectTo' not in line:
                conf+=line
            if 'ame' in line:
                host=line.split("=")[1].strip()
    return host, conf

def create_conf():
    host, conf=read_conf()
    for server in get_servers():
        if server != host:
            conf+=f'ConnectTo={server}\n'
    with open(os.path.join(tinc_path,'tinc.conf'),'w') as f:
        f.write(conf)

create_conf()


