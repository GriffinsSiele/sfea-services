---
title: Установка
description: Установка
published: true
date: 2023-03-11T15:50:08.238Z
tags: 
editor: markdown
dateCreated: 2023-03-11T13:53:51.971Z
---

# Kubernetes

```
cat /var/lib/rancher/rke2/server/node-token

mkdir -p /etc/rancher/rke2
mcedit /etc/rancher/rke2/config.yaml

curl -sfL https://get.rke2.io | INSTALL_RKE2_VERSION=v1.24.11+rke2r1 INSTALL_RKE2_TYPE="agent" sh -

node-external-ip: 46.147.123.245
token: K10dca86d68f9107fba46a6e1daa58232b26217c4444c23bc36847d44ac95c5ef22::server:c56e4895868dc4ba23b2d59f74973133
node-ip: 172.16.10.10
server: &nbsp;https://46.173.211.140:9345
write-kubeconfig-mode: "0644"<br>tls-san:<br>- "foo.local"<br>node-label:<br>- "foo=bar"<br>- "something=amazing"<br>debug: true
https://docs.rke2.io/reference/linux_agent_config

systemctl enable rke2-agent.service
systemctl start rke2-agent.service



curl -sfL https://get.rke2.io | sh -
systemctl enable rke2-server.service
curl -sfL https://get.rke2.io | INSTALL_RKE2_TYPE="agent" sh -
```