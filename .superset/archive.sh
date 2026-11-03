#!/usr/bin/env bash
# Stop serving the worktree's Herd site before Paradev removes the worktree.
. ./.superset/site-name.sh
herd unlink "$SITE" || true
