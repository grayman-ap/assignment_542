# IFT 542 — Security Assessment and Hardening of a Student Registration Web Application

> Authorised laboratory exercise only. **Fictitious data.** No real student,
> staff, or university system data is used anywhere in this project. See
> [`docs/ethics.md`](docs/ethics.md).

## Overview

This repository contains the full practical assignment: a **starter (vulnerable)
build** and a **hardened build** of a student registration web application
(PHP 8.3 + MySQL 8.4, Docker Compose), a **STRIDE threat model** with DFD and
risk register, hardened authentication and web defence controls, an
**incident-response runbook**, and an **automated test suite** that proves every
control and writes evidence under `evidence/generated/`.

| Service | URL | Purpose |
|---|---|---|
| Hardened app | `http://localhost:8085` | "After" — all controls applied |
| Starter (vulnerable) app | `http://localhost:8086` | "Before" baseline for comparison |
| MySQL | `localhost:13306` | `student_reg` (hardened) + `student_reg_vuln` (starter) |

## Requirements / Dependencies

- Docker Engine (20.10+) and Docker Compose (v2 / `docker compose` plugin)
- Git, `make`, `python3` (only for `make clone`)
- A browser for the interactive demo and for printing the report to PDF
- No PHP or MySQL is required on the host — everything runs in containers

## Quick Start

```bash
cp .env.example .env          # review ports/passwords (defaults are fine)
make setup                    # validate .env, create volumes/dirs
make build && make up         # build images and start all containers
make seed                     # seed both DBs (prints demo credentials)
make smoke                    # HTTP smoke test of both builds
```

Open `http://localhost:8085` (hardened) and `http://localhost:8086` (starter).

## Test Accounts

Seeding is deterministic for students and random for the admin:

- **Student:** `student@ftminna.edu.ng` — the seed password is printed by
  `make seed` (and stored in `db/seed-output/STUDENT_PASSWORD.txt`).
- **Admin:** `admin@ftminna.edu.ng` — random 32-char password written to
  `db/seed-output/ADMIN_PASSWORD.txt` (gitignored, never committed).
- **Starter build demo:** documented on its login page
  (`admin@ftminna.edu.ng / admin`) — intentionally weak for the baseline.

## Running the Automated Evidence

```bash
make tests        # runs ALL suites (auth, sqli, hashing, webdefences, logging, ratelimit)
make evidence     # regenerate the stable evidence samples
```

Per-suite targets: `make test-auth test-sqli test-hash test-webdef test-rate test-logs`.
Each full run writes `evidence/generated/run-<TIMESTAMP>/` with per-suite output.
Latest stable samples: `evidence/generated/password-storage-evidence.txt` and
`evidence/generated/logs-sample.log`.

## Project Layout

```
app/            hardened application (src/, public/, views/, assets/)
vulnerable/     starter application (before-hardening baseline)
db/             schema.sql, seed scripts, seed-output/ (gitignored)
docker-init/    DB bootstrap (creates both schemas + least-privilege grants)
docker/         Apache vhosts + preview catalogue container
tests/          automated evidence suites + helpers
tools/          clone_project.py (personalise + push this project)
docs/           report.html, dfd.svg, STRIDE.md, risk-register.md, runbook.md, ethics.md
evidence/generated/   generated test evidence (gitignored)
Makefile        every command: build/up/down/reset/seed/tests/clone/...
clone.json      your personalisation values (see "Clone for another student")
```

## Report and Submission Checklist

The technical report is `docs/report.html` (8-12 pages when printed). Open it
in a browser and use print-to-PDF to produce
`2021-1-84154CF_IFT542.pdf`.

- [ ] PDF report covering Tasks 1-3, STRIDE worksheet, DFD, risk register, controls and test evidence
- [ ] Source pushed to https://github.com/grayman-ap/assignment_542.git on branch `main`
- [ ] `evidence/generated/` contains the last full `make tests` run
- [ ] This README personalised (matric no, name, repository link)
- [ ] Ethics statement signed in `docs/ethics.md`
- [ ] Fictitious data only; no secrets in the repository

## Personalise & Clone for Another Student

Other students can clone and personalise the whole project (new student name,
matric no, date, their GitHub repo) from a single JSON file:

```bash
cp clone.json.example clone.json   # edit values: matric_no, full_name, email,
                                   # department, github_url, date
make clone                         # build ./out/<matric>_IFT542 with placeholders replaced
make clone-push                    # clone + git init + first commit + push to github_url
make clone-create                  # clone + create the GitHub repo (gh) + push
```

`tools/clone_project.py` copies the source template (excluding `.git`, `.env`,
`clone.json`, generated evidence and secrets), replaces the `{{PLACEHOLDER}}`
values across the report, README and docs, and optionally creates/commits/pushes
a fresh repository.

## Authorised-Lab and Ethics Notes

- Run locally on `localhost` only; never attach a real database or PII.
- The starter build is intentionally vulnerable — do **not** deploy it anywhere
  reachable by others.
- See [`docs/runbook.md`](docs/runbook.md) for incident response and
  [`docs/ethics.md`](docs/ethics.md) for the signed ethics statement.
