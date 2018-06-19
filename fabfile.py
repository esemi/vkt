#! /usr/bin/env python3
# -*- coding: utf-8 -*-

import os

from fabric.api import env, run, put, cd
from fabric.contrib.files import exists

env.user = 'vkt'

REMOTE_PATH = os.path.join('/home', env.user)
APP_PATH = os.path.join(REMOTE_PATH, env.user)
LOCAL_PATH = os.path.dirname(__file__)

FOLDERS = ('www', 'src')


def tests():
    pass


def deploy():
    if not exists(APP_PATH):
        run('mkdir -p %s' % APP_PATH)
    for folder in FOLDERS:
        put(os.path.join(LOCAL_PATH, folder), APP_PATH)
