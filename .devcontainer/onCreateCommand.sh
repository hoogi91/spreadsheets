#!/usr/bin/env bash
set -x

wait-for-it db:3306

echo 'create database if not exists `typo3-v11`' | mysql -h db -u root -proot
echo 'create database if not exists `typo3-v10`' | mysql -h db -u root -proot
