# real
```shell
export DATABASE=postgresql://postgres:hZOiFBAu2eUwBU5squ1fINhp2qygcMqD5zWY2kDrkuyWcZOPchP5VlwZc0t2B3SF@hasura-pooler.cnpg-system.svc.cluster.local:5432/app
\c app
alter schema fns owner to app;
grant all privileges on all tables in schema fns to app;
grant all privileges on all sequences in schema fns to app;
```

# dev
```shell
export DATABASE=postgresql://localhost/fns
```