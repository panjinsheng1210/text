#!/bin/bash

function get_dbscheme()
{
  case $1 in
  0)
    scheme="-n public -n award"
	;;
  1)
    scheme="-n award"
	;;
  2)
    scheme="-n public"
	;;
  *)
    scheme=
	;;
  esac
  echo "$scheme"
}
function create_db_local()
{
    ssh root@$CI_LOCAL_SERVER "cd $CI_LOCAL_DOCKER_PATH && docker-compose exec -T -u postgres pgsql psql -c \"drop role if exists $1;create role $1 with CREATEDB LOGIN password '${dbpasswd}';\""
    ssh root@$CI_LOCAL_SERVER "cd $CI_LOCAL_DOCKER_PATH && docker-compose exec -T pgsql bash -c ' \
    echo -e \"host\t${1}\t${1}\t0.0.0.0/0\tmd5\" >> /var/lib/postgresql/data/pgdata/pg_hba.conf' \
    && docker-compose kill -s SIGHUP pgsql"
}
function create_db_remote_docker()
{
    ssh root@$CI_REMOTE_SERVER "cd $CI_REMOTE_DOCKER_PATH && docker-compose exec -T -u postgres pgsql psql -c \"drop role if exists $1;create role $1 with CREATEDB LOGIN password '${dbpasswd}';\""
    ssh root@$CI_REMOTE_SERVER "cd $CI_REMOTE_DOCKER_PATH && docker-compose exec -T pgsql bash -c ' \
    echo -e \"host\t${1}\t${1}\t0.0.0.0/0\tmd5\" >> /var/lib/postgresql/data/pgdata/pg_hba.conf' \
    && docker-compose kill -s SIGHUP pgsql"
}
function create_db_remote()
{
    ssh root@$CI_REMOTE_SERVER "psql -U postgres -c \"drop role if exists $1;create role $1 with CREATEDB LOGIN password '${dbpasswd}';\""
    ssh root@$CI_REMOTE_SERVER "echo -e \"host\t${1}\t${1}\t0.0.0.0/0\tmd5\" >> /etc/postgresql/10/main/pg_hba.conf \
    && systemctl reload postgresql"
}
function clean_scheme()
{
  case $2 in
  0)
    scheme_sql="DROP SCHEMA  IF EXISTS  public,award  CASCADE;create schema public;create schema award;"
	;;
  1)
    scheme_sql="DROP SCHEMA  IF EXISTS  award  CASCADE;create schema award;"
	;;
  2)
    scheme_sql="DROP SCHEMA  IF EXISTS  public  CASCADE;create schema public;"
	;;
  *)
    ssh root@$CI_REMOTE_SERVER "export PGPASSWORD=${dbpasswd} && dropdb -h localhost -U $1 --if-exists $1 && createdb -h localhost -U $1 -E utf-8 $1"
    scheme_sql="CREATE EXTENSION  IF NOT EXISTS  plpgsql;"
	;;
  esac
  ssh root@$CI_REMOTE_SERVER "export PGPASSWORD=${dbpasswd} && psql -h localhost -U $1 -d $1 -c \"${scheme_sql}\""
}
function clean_scheme_local()
{
  case $2 in
  0)
    scheme_sql="DROP SCHEMA  IF EXISTS  public,award  CASCADE;create schema public;create schema award;"
	;;
  1)
    scheme_sql="DROP SCHEMA  IF EXISTS  award  CASCADE;create schema award;"
	;;
  2)
    scheme_sql="DROP SCHEMA  IF EXISTS  public  CASCADE;create schema public;"
	;;
  *)
    ssh root@$CI_REMOTE_SERVER "cd $CI_LOCAL_DOCKER_PATH && docker-compose exec -T  -e PGHOST=localhost -e PGUSER=$1 -e PGPASSWORD=${dbpasswd} pgsql dropdb --if-exists $1 && createdb -E utf-8 $1"
    scheme_sql="CREATE EXTENSION  IF NOT EXISTS  plpgsql;"
	;;
  esac
  ssh root@$CI_LOCAL_SERVER "cd $CI_LOCAL_DOCKER_PATH && docker-compose exec -T  -e PGHOST=localhost -e PGUSER=$1 -e PGPASSWORD=${dbpasswd} pgsql  psql -d $1 -c \"${scheme_sql}\""
}
function clean_scheme_remote_docker()
{
  case $2 in
  0)
    scheme_sql="DROP SCHEMA  IF EXISTS  public,award  CASCADE;create schema public;create schema award;"
	;;
  1)
    scheme_sql="DROP SCHEMA  IF EXISTS  award  CASCADE;create schema award;"
	;;
  2)
    scheme_sql="DROP SCHEMA  IF EXISTS  public  CASCADE;create schema public;"
	;;
  *)
    ssh root@$CI_REMOTE_SERVER "cd $CI_LOCAL_DOCKER_PATH && docker-compose exec -T  -e PGHOST=localhost -e PGUSER=$1 -e PGPASSWORD=${dbpasswd} pgsql dropdb --if-exists $1 && createdb -E utf-8 $1"
    scheme_sql="CREATE EXTENSION  IF NOT EXISTS  plpgsql;"
	;;
  esac
  ssh root@$CI_REMOTE_SERVER "cd $CI_REMOTE_DOCKER_PATH && docker-compose exec -T  -e PGHOST=localhost -e PGUSER=$1 -e PGPASSWORD=${dbpasswd} pgsql  psql -d $1 -c \"${scheme_sql}\""
}
function bak_local_db()
{
  scheme="`get_dbscheme $3`"
  ssh root@$CI_LOCAL_SERVER "cd $CI_LOCAL_DOCKER_PATH && docker-compose exec -T -u postgres pgsql pg_dump -Fc -O $scheme -c --if-exists $1 > $2"
}
function bak_remote_db_docker()
{
  scheme="`get_dbscheme $3`"
  ssh root@$CI_REMOTE_SERVER "cd $CI_REMOTE_DOCKER_PATH && docker-compose exec -T -u postgres pgsql pg_dump -Fc -O $scheme -c --if-exists $1 > $2"
}
function bak_local_db_to_ci()
{
  scheme="`get_dbscheme $3`"
  ssh root@$CI_LOCAL_SERVER "cd $CI_LOCAL_DOCKER_PATH && docker-compose exec -T -u postgres pgsql pg_dump -Fc -O $scheme -c --if-exists $1" > $2
}
function bak_remote_db_to_ci_docker()
{
  scheme="`get_dbscheme $3`"
  ssh root@$CI_REMOTE_SERVER "cd $CI_REMOTE_DOCKER_PATH && docker-compose exec -T -u postgres pgsql pg_dump -Fc -O $scheme -c --if-exists $1" > $2
}
function bak_remote_db_to_ci()
{
  scheme="`get_dbscheme $3`"
  ssh root@$CI_REMOTE_SERVER "pg_dump -U postgres -Fc -O $scheme -c --if-exists $1" > $2
}
function bak_remote_db()
{
  ssh root@$CI_REMOTE_SERVER "pg_dump -U postgres -O -Fc $1 > $2"
}
function rest_remote_db()
{
  scheme=`get_dbscheme $3`
  clean_scheme $1 $3 && ssh root@$CI_REMOTE_SERVER "export PGPASSWORD=${dbpasswd} && pg_restore -h localhost --disable-triggers -O -c --if-exists $scheme -U $1 -d $1 $2"
}
function rest_local_db()
{
  scheme=`get_dbscheme $3`
  clean_scheme_local $1 $3 && ssh root@$CI_LOCAL_SERVER "cd $CI_LOCAL_DOCKER_PATH && docker-compose exec -T -e PGHOST=localhost -e PGUSER=$1 -e PGPASSWORD=${dbpasswd} pgsql pg_restore --disable-triggers -O -c --if-exists $scheme -d $1 $2"
}
function rest_remote_db_docker()
{
  scheme=`get_dbscheme $3`
  clean_scheme_remote_docker $1 $3 && ssh root@$CI_REMOTE_SERVER "cd $CI_REMOTE_DOCKER_PATH && docker-compose exec -T -e PGHOST=localhost -e PGUSER=$1 -e PGPASSWORD=${dbpasswd} pgsql pg_restore --disable-triggers -O -c --if-exists $scheme -d $1 $2"
}
function exist_local_db()
{
  echo $(ssh root@$CI_LOCAL_SERVER "cd $CI_LOCAL_DOCKER_PATH && docker-compose exec -T -u postgres pgsql psql -l -A -t|cut -f 1 -d '|'|grep $1")
}
function exist_remote_db_docker()
{
  echo $(ssh root@$CI_REMOTE_SERVER "cd $CI_REMOTE_DOCKER_PATH && docker-compose exec -T -u postgres pgsql psql -l -A -t|cut -f 1 -d '|'|grep $1")
}
function exist_remote_db()
{
  dbexist=$(ssh root@$CI_REMOTE_SERVER "psql -U postgres -l -A -t|cut -f 1 -d '|'|grep $1")
  echo $dbexist
}
function exist_local_site()
{
  echo $(ssh root@$CI_LOCAL_SERVER "cd $CI_LOCAL_SITE_CONF_PATH && [ -f ${CI_PROJECT_PATH_SLUG}.conf ] && echo true || echo false")
}
function exist_remote_site_docker()
{
  echo $(ssh root@$CI_REMOTE_SERVER "cd $CI_REMOTE_SITE_CONF_PATH && [ -f ${CI_PROJECT_PATH_SLUG}.conf ] && echo true || echo false")
}
function exist_remote_site()
{
  echo $(ssh root@$CI_REMOTE_SERVER "cd $CI_REMOTE_SITE_CONF_PATH && [ -f ${CI_PROJECT_PATH_SLUG}.conf ] && echo true || echo false")
}
function db_password()
{
  if [[ "$CI_COMMIT_REF_NAME" = "master" ]]; then
    dbpasswd=$dbpasswd_remote
  fi
  if [[ -z "$dbpasswd" ]]; then
    if [[ -f "dbpasswd.txt" ]]; then
      dbpasswd=$(cat dbpasswd.txt)
	else
	  dbpasswd=$(openssl rand -base64 18)
	  echo -n -E $dbpasswd > dbpasswd.txt
	  echo -e "dbuser:$DBNAME\r\ndbpassword:${dbpasswd}\r\ndbname:$DBNAME"> db.txt
	fi
  fi
  echo $dbpasswd
  export dbpasswd
}
function sync_db()
{
  if [[ -z "`exist_local_db $1`" ]]; then
    echo "local database not exist."
    exit 1
  fi
  
  if [[ -z "`exist_remote_db $1`" ]]; then
    ssh root@$CI_REMOTE_SERVER "export PGPASSWORD=${dbpasswd} && createdb -h localhost -U $1 -E utf-8 $1 && psql -h localhost -U $1 -c \"create schema public;CREATE EXTENSION  IF NOT EXISTS  plpgsql;\""
  else
    bak_db_file=`date +"%Y-%m-%d_%H:%M:%S"`_${RANDOM}.bak
    bak_remote_db $1 $CI_REMOTE_PATH/$CI_PROJECT_PATH_SLUG/db/$bak_db_file
  fi
  db_file=deploy.bak
  remote_db_file=$CI_REMOTE_PATH/$CI_PROJECT_PATH_SLUG/db/deploy.bak
  remote_db_path=$CI_REMOTE_PATH/$CI_PROJECT_PATH_SLUG/db/
  [ "$DBFILE" == "true" ] || bak_local_db_to_ci $1 $db_file $2
  if [[ -f $db_file ]]; then
    ssh root@$CI_REMOTE_SERVER "mkdir -p $remote_db_path"
    scp $db_file root@$CI_REMOTE_SERVER:$remote_db_file
    if [[ $? -eq 0 ]]; then
      rest_remote_db $1 $remote_db_file $2
    fi
  fi
}
function sync_db_docker()
{
  if [[ -z "`exist_local_db $1`" ]]; then
    echo "local database not exist."
    exit 1
  fi

  if [[ -z "`exist_remote_db_docker $1`" ]]; then
	ssh root@$CI_REMOTE_SERVER "cd $CI_REMOTE_DOCKER_PATH && docker-compose exec -T -e PGHOST=localhost -e PGUSER=$1 -e PGPASSWORD=${dbpasswd}  pgsql bash -c 'createdb -E utf-8 $1 && psql -h localhost -c \"DROP SCHEMA  IF EXISTS  public;create schema public;CREATE EXTENSION  IF NOT EXISTS  plpgsql;\"'"
  else
    bak_db_file=`date +"%Y-%m-%d_%H:%M:%S"`_${RANDOM}.bak
    bak_remote_db_docker $1 $CI_REMOTE_PATH/$CI_PROJECT_PATH_SLUG/db/$bak_db_file
  fi
  db_file=deploy.bak
  remote_db_file=$CI_REMOTE_PG_BAK_PATH/$CI_PROJECT_PATH_SLUG_deploy.bak
  remote_db_path=$CI_REMOTE_PG_BAK_PATH/
  [ "$DBFILE" == "true" ] || bak_local_db_to_ci $1 $db_file $2
  if [[ -f $db_file ]]; then
    ssh root@$CI_REMOTE_SERVER "mkdir -p $remote_db_path"
    scp $db_file root@$CI_REMOTE_SERVER:$CI_REMOTE_DOCKER_PG_BAK_PATH/$CI_PROJECT_PATH_SLUG_deploy.bak
    if [[ $? -eq 0 ]]; then
      rest_remote_db_docker $1 $remote_db_file $2
    fi
  fi
}
function pull_db()
{
  if [[ -z "`exist_local_db $1`" ]]; then
    create_db_local $1
  fi

  if [[ -z "`exist_remote_db $1`" ]]; then
      echo "remote database not exist."
      exit 1
  else
    bak_db_file=`date +"%Y-%m-%d_%H:%M:%S"`_${RANDOM}.bak
    bak_local_db $1 $CI_LOCAL_PATH/$CI_PROJECT_PATH_SLUG/db/$bak_db_file 0
  fi
  db_file=deploy.bak
  local_db_file=${CI_LOCAL_DOCKER_PG_BAK_PATH}/${CI_PROJECT_PATH_SLUG}_deploy.bak
  [ "$DBFILE" == "true" ] || bak_remote_db_to_ci $1 $db_file $2
  if [[ -f $db_file ]]; then
    scp $db_file root@$CI_LOCAL_SERVER:$local_db_file
    rm $db_file
    if [[ $? -eq 0 ]]; then
      rest_local_db $1 ${CI_LOCAL_PG_BAK_PATH}/${CI_PROJECT_PATH_SLUG}_deploy.bak $2
    fi
  fi
}
function pull_db_docker()
{
  if [[ -z "`exist_local_db $1`" ]]; then
    create_db_local $1
  fi

  if [[ -z "`exist_remote_db_docker $1`" ]]; then
      echo "remote database not exist."
      exit 1
  else
    bak_db_file=`date +"%Y-%m-%d_%H:%M:%S"`_${RANDOM}.bak
    bak_local_db $1 $CI_LOCAL_PATH/$CI_PROJECT_PATH_SLUG/db/$bak_db_file 0
  fi
  db_file=deploy.bak
  local_db_file=${CI_LOCAL_DOCKER_PG_BAK_PATH}/${CI_PROJECT_PATH_SLUG}_deploy.bak
  [ "$DBFILE" == "true" ] || bak_remote_db_to_ci_docker $1 $db_file $2
  if [[ -f $db_file ]]; then
    scp $db_file root@$CI_LOCAL_SERVER:$local_db_file
    rm $db_file
    if [[ $? -eq 0 ]]; then
      rest_local_db $1 ${CI_LOCAL_PG_BAK_PATH}/${CI_PROJECT_PATH_SLUG}_deploy.bak $2
    fi
  fi
}
function deploy_db_remote()
{
  if [[ -f "db/${1}.bak" && -z "`exist_remote_db $1`" ]]; then \
	create_db_remote $1
    sync_db_docker $1 init
  fi
}
function deploy_db_remote_docker()
{
  if [[ -f "db/${1}.bak" && -z "`exist_remote_db $1`" ]]; then \
	create_db_remote_docker $1
    sync_db_docker $1 init
  fi
}
function restore_db_local()
{
  ssh root@$CI_LOCAL_SERVER "cd $CI_LOCAL_DOCKER_PATH && docker-compose exec -T -e PGHOST=localhost -e PGUSER=$1 -e PGPASSWORD=${dbpasswd} pgsql bash -c ' \
  createdb  $1 -E utf-8 \
  && psql -c \"create schema public;CREATE EXTENSION  IF NOT EXISTS  plpgsql;\" \
  && psql -d $1 -f $2'"
}
function deploy_db_local()
{
  if [[ -f "db/${DBNAME}.bak" && -z "`exist_local_db $1`" ]]; then
	create_db_local $1
    scp -qB db/${1}.sql root@$CI_LOCAL_SERVER:$CI_LOCAL_DOCKER_PG_BAK_PATH/
	restore_db_local $1 $CI_LOCAL_PG_BAK_PATH/${1}.sql
  fi
}
function site_local()
{
  if [[ "$(exist_local_site)" == "true" ]]; then
    #修改域名
    ssh root@$CI_LOCAL_SERVER " \
	cd $CI_LOCAL_SITE_CONF_PATH \
    && sed -i 's/^\s*listen\s\+.*$/\tlisten $LOCAL_PORT;/' ${CI_PROJECT_PATH_SLUG}.conf \
    && sed -i 's/^\s*server_name\s\+.*$/\tserver_name $LOCAL_DOMAIN;/' ${CI_PROJECT_PATH_SLUG}.conf \
    && cd $CI_LOCAL_DOCKER_PATH \
    && docker-compose exec -T web nginx -s reload"
  else
    ssh root@$CI_LOCAL_SERVER " \
	cd $CI_LOCAL_SITE_CONF_PATH \
    && sed 's/shop-template/${CI_PROJECT_PATH_SLUG}/g'  shop-template.conf>${CI_PROJECT_PATH_SLUG}.conf \
    && sed -i 's/^\s*listen\s\+.*$/\tlisten $LOCAL_PORT;/' ${CI_PROJECT_PATH_SLUG}.conf \
    && sed -i 's/^\s*server_name\s\+.*$/\tserver_name $LOCAL_DOMAIN;/' ${CI_PROJECT_PATH_SLUG}.conf \
    && cd ../sites-enabled \
    && ln -sf ../sites-available/${CI_PROJECT_PATH_SLUG}.conf ${CI_PROJECT_PATH_SLUG}.conf \
    && cd $CI_LOCAL_DOCKER_PATH \
    && docker-compose exec -T web nginx -s reload"
  fi
}
function site_remote()
{
  if [[ "$(exist_remote_site)" == "true" ]]; then
    ssh root@$CI_REMOTE_SERVER " \
    cd $CI_REMOTE_SITE_CONF_PATH \
    && sed -i 's/^\s*ServerName\s\+.*$/\tServerName $REMOTE_DOMAIN/' ${CI_PROJECT_PATH_SLUG}.conf \
    && apachectl graceful"
  else
    ssh root@$CI_REMOTE_SERVER " \
    cd $CI_REMOTE_SITE_CONF_PATH \
    && sed 's/shop-template/${CI_PROJECT_PATH_SLUG}/g'  shop-template.conf>${CI_PROJECT_PATH_SLUG}.conf \
    && sed -i 's/^\s*ServerName\s\+.*$/\tServerName $REMOTE_DOMAIN/' ${CI_PROJECT_PATH_SLUG}.conf \
    && cd ../sites-enabled \
    && ln -sf ../sites-available/${CI_PROJECT_PATH_SLUG}.conf ${CI_PROJECT_PATH_SLUG}.conf \
    && apachectl graceful"
  fi
}
function site_remote_docker()
{
  if [[ "$(exist_remote_site_docker)" == "true" ]]; then
    #修改域名
    ssh root@$CI_REMOTE_SERVER " \
	cd $CI_LOCAL_SITE_CONF_PATH \
    && sed -i 's/^\s*server_name\s\+.*$/\tserver_name $REMOTE_DOMAIN;/' ${CI_PROJECT_PATH_SLUG}.conf \
    && cd $CI_REMOTE_DOCKER_PATH \
    && docker-compose exec -T web nginx -s reload"
  else
    ssh root@$CI_REMOTE_SERVER " \
	cd $CI_REMOTE_SITE_CONF_PATH \
    && sed 's/shop-template/${CI_PROJECT_PATH_SLUG}/g'  shop-template.conf>${CI_PROJECT_PATH_SLUG}.conf \
    && sed -i 's/^\s*server_name\s\+.*$/\tserver_name $REMOTE_DOMAIN;/' ${CI_PROJECT_PATH_SLUG}.conf \
    && cd ../sites-enabled \
    && ln -sf ../sites-available/${CI_PROJECT_PATH_SLUG}.conf ${CI_PROJECT_PATH_SLUG}.conf \
    && cd $CI_REMOTE_DOCKER_PATH \
    && docker-compose exec -T web nginx -s reload"
  fi
}
function sync_password()
{
  if [[ "$CI_BUILD_REF_NAME" = "master" ]]; then
    dbhost=mysql
  else
    dbhost=mysql
  fi
  ls -l
  ls -l config
  if [[ -n "$dbpasswd" ]]; then
    sed -i "s/^.*password.*$/\t'password' => '${dbpasswd//\//\\\/}',/" config/database.php
    sed -i "s/^.*username.*$/\t'username' => 'root',/" config/database.php
    sed -i "s/^.*database.*$/\t'database' => '${DBNAME}',/" config/database.php
    sed -i "s/^.*'hostname'\s*=>.*$/\t'hostname' => '${dbhost}',/" config/database.php
  fi
}
