#!/bin/bash
set -e
cd $(dirname $0)
work_path=`pwd`

svn cleanup
svn revert -R .

cd ..
local_svn_url=`svn info|grep ^URL|awk '{print $2}'`
if [ "$local_svn_url" != "$2" ]&&[ ! -z "`echo "$2"|grep "//"`" ];then
   echo "branch switch start"
   svn sw --ignore-ancestry $2
   svn revert -R .
   echo "branch switch end"
fi

if [ ! -z "$1" ];then
   svn up -r $1 --force --accept mine-conflict
else
   svn up --force --accept mine-conflict
fi
