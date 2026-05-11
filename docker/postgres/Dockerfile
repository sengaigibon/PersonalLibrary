FROM postgres:16-alpine

ENV LANG en_US.utf8

RUN mkdir -p /var/lib/postgresql/data /var/run/postgresql

RUN chown -R postgres:postgres /var/lib/postgresql/data /var/run/postgresql

COPY docker/postgres/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]