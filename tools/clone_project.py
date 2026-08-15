#!/usr/bin/env python3
"""
clone_project.py - personalise a MATRICNO_IFT542 clone for any student.

Reads a structured JSON file (clone.json) containing the values needed to
clone this project completely for another student, validates them, copies the
template into an output directory named MATRICNO_IFT542, and replaces every
placeholder with the student's details.

Optionally initialises Git and pushes to GitHub with the standard workflow:

    git init -b main
    git add -A
    git commit -m "first commit"
    git branch -M main
    git remote add origin <github_url>
    git push -u origin main

Usage:
    python3 tools/clone_project.py --config clone.json [--git] [--push] [--create-repo]

Placeholders replaced:
    2021/1/84154CF, 2021-1-84154CF, Peter Adeshina, peteradeshina3@gmail.com,
    Information Technology, https://github.com/grayman-ap/assignment_542.git, 2026-08-13
"""
import argparse
import datetime
import json
import re
import shutil
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

EXCLUDE = {
    '.git', '.env', 'clone.json', 'evidence/generated', 'db/seed-output',
    'app/logs', 'app/storage', 'out', '__pycache__', '*.pyc', '*.log',
}

TEXT_EXTS = {
    '.php', '.md', '.html', '.sql', '.sh', '.txt', '.yml', '.conf',
    '.css', '.py', '.example', '.json',
}

REQUIRED = ['matric_no', 'full_name', 'email']
OPTIONAL = ['department', 'github_url', 'date', 'output_dir']


def fail(msg: str) -> None:
    print(f"[clone] ERROR: {msg}", file=sys.stderr)
    sys.exit(1)


def load_config(path: Path) -> dict:
    if not path.is_file():
        fail(f"config file not found: {path} (copy clone.json.example to clone.json first)")
    try:
        cfg = json.loads(path.read_text(encoding='utf-8'))
    except json.JSONDecodeError as e:
        fail(f"invalid JSON in {path}: {e}")
    for key in REQUIRED:
        if not cfg.get(key):
            fail(f"missing required field '{key}' in {path}")
    return cfg


def sanitise_matric(matric: str) -> str:
    m = re.fullmatch(
        r'[A-Za-z0-9]+/[1-2](?:/|-)[A-Za-z0-9]+',
        matric.strip()
    )
    if not m:
        fail(f"matric_no '{matric}' does not match the FUT Minna format "
             f"(e.g. 2024/1-12345 or 2021/1/84154CF)")
    return matric.strip().replace('/', '-')


def filter_excluded(src: Path) -> bool:
    name = src.name
    if name in EXCLUDE or any(src.match(p) for p in EXCLUDE):
        return True
    return False


def copy_template(dst: Path) -> None:
    for item in ROOT.iterdir():
        if item.name.startswith('.') or item.name == '__pycache__':
            continue
        if filter_excluded(item):
            continue
        _copy_any(item, dst / item.name)


def _copy_any(src: Path, dst: Path) -> None:
    if src.is_dir():
        dst.mkdir(parents=True, exist_ok=True)
        for child in src.iterdir():
            if filter_excluded(child):
                continue
            _copy_any(child, dst / child.name)
    else:
        shutil.copy2(src, dst)


def replace_placeholders(dst: Path, values: dict) -> int:
    replaced = 0
    for path in dst.rglob('*'):
        if not path.is_file() or path.suffix not in TEXT_EXTS:
            continue
        try:
            text = path.read_text(encoding='utf-8')
        except (UnicodeDecodeError, OSError):
            continue
        if '{{' not in text:
            continue
        for key, val in values.items():
            text = text.replace('{{' + key + '}}', val)
        path.write_text(text, encoding='utf-8')
        replaced += 1
    return replaced


def git_init(dst: Path, cfg: dict) -> None:
    subprocess.run(['git', 'init', '-b', 'main'], cwd=dst, check=True,
                   capture_output=True)
    if cfg.get('github_url'):
        subprocess.run(['git', 'remote', 'add', 'origin', cfg['github_url']],
                       cwd=dst, check=True, capture_output=True)
        print(f"[clone] git remote origin -> {cfg['github_url']}")
    subprocess.run(['git', 'add', '-A'], cwd=dst, check=True, capture_output=True)
    subprocess.run(['git', 'commit', '-m', 'first commit'],
                   cwd=dst, check=True, capture_output=True)
    print("[clone] initial commit created (main)")


def git_push(dst: Path, cfg: dict, create_repo: bool = False) -> None:
    url = cfg.get('github_url', '').strip()
    if not url:
        print("[clone] no github_url in config - skipping push", file=sys.stderr)
        sys.exit(1)

    if create_repo:
        try:
            subprocess.run(['gh', 'repo', 'create', url, '--private', '--source', '.', '--push'],
                           cwd=dst, check=True, capture_output=True)
            print(f"[clone] created and pushed: {url}")
            return
        except (subprocess.CalledProcessError, FileNotFoundError):
            # Repo may already exist or gh may be unavailable; fall through to plain push.
            pass

    print("[clone] pushing to GitHub ...")
    try:
        subprocess.run(['git', 'push', '-u', 'origin', 'main'], cwd=dst, check=True,
                       capture_output=True, text=True)
    except subprocess.CalledProcessError as e:
        print(f"[clone] push failed: {e.stderr.strip()}", file=sys.stderr)
        sys.exit(1)
    print(f"[clone] pushed to {url} (branch main)")


def main() -> None:
    ap = argparse.ArgumentParser(description='Provision a MATRICNO_IFT542 clone.')
    ap.add_argument('--config', default=str(ROOT / 'clone.json'))
    ap.add_argument('--git', action='store_true', help='git init + commit + set origin')
    ap.add_argument('--push', action='store_true',
                    help='git init, commit, set origin and push to GitHub')
    ap.add_argument('--create-repo', action='store_true',
                    help='with --push, create the GitHub repo first via `gh` if missing')
    args = ap.parse_args()

    cfg = load_config(Path(args.config))
    matric_file = sanitise_matric(cfg['matric_no'])
    output_root = Path(cfg.get('output_dir', './out'))
    dst = output_root / f"{matric_file}_IFT542"

    if dst.exists():
        fail(f"destination already exists: {dst} (remove it or choose another output_dir)")

    values = {
        'MATRIC_NO':      cfg['matric_no'],
        'MATRIC_NO_FILE': matric_file,
        'STUDENT_NAME':   cfg['full_name'].strip(),
        'STUDENT_EMAIL':  cfg['email'].strip(),
        'DEPARTMENT':     cfg.get('department', 'Information Technology'),
        'GITHUB_URL':     cfg.get('github_url', ''),
        'DATE':           cfg.get('date', datetime.date.today().isoformat()),
    }

    dst.mkdir(parents=True)
    copy_template(dst)
    n = replace_placeholders(dst, values)

    manifest = dst / 'clone-manifest.txt'
    manifest.write_text(
        '\n'.join([
            'IFT 542 clone manifest',
            '=====================',
            f"Matric number : {values['MATRIC_NO']}",
            f"Folder name   : {dst.name}",
            f"Student       : {values['STUDENT_NAME']} <{values['STUDENT_EMAIL']}>",
            f"Department    : {values['DEPARTMENT']}",
            f"GitHub        : {values['GITHUB_URL'] or '(none set)'}",
            f"Date          : {values['DATE']}",
            f"Placeholders  : {n} files updated",
            '',
            'Submission checklist',
            '--------------------',
            '1. All files use MATRICNO_IFT542 naming (PDF should be '
            f"{matric_file}_IFT542.pdf).",
            '2. Project runs from README with fictitious data only.',
            '3. Secrets are placeholders only (see .env.example).',
            '4. Evidence is readable, redacted and referenced from the report.',
            '5. Tech report (docs/report.html -> Save as PDF) covers Tasks 1-3.',
        ])
    )

    print(f"[clone] created {dst}/")
    print(f"[clone] {n} files personalised for {values['STUDENT_NAME']} ({values['MATRIC_NO']})")
    if args.push:
        git_init(dst, cfg)
        git_push(dst, cfg, create_repo=args.create_repo)
    elif args.git:
        git_init(dst, cfg)
    else:
        print("[clone] run: cd {0} && git init && git add -A && git commit -m \"first commit\"".format(dst))
    print(f"[clone] next: cd {dst} && cp .env.example .env && make up && make seed && make tests")
    print(f"[clone] manifest: {dst / 'clone-manifest.txt'}")


if __name__ == '__main__':
    main()
