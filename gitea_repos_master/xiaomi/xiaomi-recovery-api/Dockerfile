FROM git.i-sphere.local/isphere-images/docker-chrome-with-python:126.0-3.11
ARG GIT_HTTP_URL_WITH_CREDENTIALS
WORKDIR /app
COPY . .
RUN pip install --no-cache-dir pipenv==2022.1.8 && \
    pipenv install --skip-lock --system --deploy && \
    pip uninstall pipenv -y
CMD [ "python3", "start.py" ]