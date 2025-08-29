FROM apache/superset:pr-17782  

USER root

RUN pip install psycopg2-binary
RUN pip install sqlalchemy-redshift 
RUN pip install Authlib        
RUN pip install mysqlclient

COPY ./superset_config.py ${PYTHONPATH}/.

ENV CONFIG_PATH_ENV_VAR=${PYTHONPATH}/superset_config.py
