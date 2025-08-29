FROM git.i-sphere.ru/viber/viber-tpl:latest
WORKDIR /app
COPY . .
ARG GIT_USER
ARG GIT_TOKEN
RUN pipenv install --clear --skip-lock --system --deploy && pip uninstall pipenv -y
ENV TZ="Europe/Moscow"
USER viber
CMD ["python","main.py"]