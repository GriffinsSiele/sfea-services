set
    allow_experimental_database_materialized_mysql = 1;

create database isphere 
engine = MaterializedMySQL('5.200.56.2:3306', 'isphere', 'clickhouse', 'clickhouse') 
settings allows_query_when_mysql_lost = true,
         max_wait_time_when_mysql_unavailable = 10000,
         materialized_mysql_tables_list = 'RequestNew,ResponseNew' 
table override RequestNew (
    partition by intDiv(id, 4294967)
),
table override ResponseNew (
    partition by intDiv(id, 4294967)
);
