FROM elixir:1.15

RUN mix local.hex --force && \
    mix local.rebar --force

RUN mix archive.install hex phx_new 1.7.2 --force

RUN apt-get update && apt-get install -y inotify-tools

WORKDIR /app

CMD ["mix", "phx.server"]
