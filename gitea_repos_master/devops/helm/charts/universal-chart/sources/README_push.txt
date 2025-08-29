helm plugin install https://github.com/chartmuseum/helm-push
helm repo add  --username drone-bot --password 89.............  drone-bot http://gitea-http.gitea.svc.cluster.local/api/packages/drone-bot/helm
--------------------------
helm package ./
helm cm-push ./sources*.tgz drone-bot