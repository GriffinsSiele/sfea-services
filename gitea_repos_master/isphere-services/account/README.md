# Create client 

```shell
hydra create client -e http://localhost:4445 \
  --name 'iSphere OAuth2 Playground' \
  --client-uri 'https://i-sphere.ru' \
  --contact 'soulkoden@gmail.com' \
  --owner 'admin@i-sphere.ru' \
  --redirect-uri http://localhost:3000/ \
  --grant-type authorization_code,refresh_token,client_credentials \
  --scope openid,offline \
  --token-endpoint-auth-method client_secret_post \
  --response-type code,id_token 
```
