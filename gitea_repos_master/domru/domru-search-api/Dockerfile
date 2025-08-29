FROM git.i-sphere.local/isphere-images/python:3.11-slim-buster
WORKDIR /app
ARG GIT_HTTP_URL_WITH_CREDENTIALS
ENV TZ="Europe/Moscow"
COPY . .
RUN pip install --no-cache-dir pipenv==2022.1.8 && \
    pipenv install --skip-lock --system --deploy && \
    pip uninstall pipenv -y
RUN chmod +x start.sh
CMD ["./start.sh"]
