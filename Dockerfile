FROM alpine:edge
LABEL MAINTAINER Yurii Fisakov <fisakov.root@gmail.com>
ENV TIMEZONE Europe/Kiev
ENV PHP_MEMORY_LIMIT    512M
RUN echo "http://dl-cdn.alpinelinux.org/alpine/edge/testing" >> /etc/apk/repositories && apk update
RUN apk add --update --no-cache tzdata && \
    cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && \
    echo "${TIMEZONE}" > /etc/timezone
RUN apk add --update --no-cache sed php7 php-xmlwriter php7-curl php7-json

RUN mkdir /www /export && \
    apk del tzdata && \
    rm -rf /var/cache/apk/*

COPY src/* /www/
COPY entrypoint.sh /entrypoint.sh
RUN chmod 0755 /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
