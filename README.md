# DashWP – Custom WordPress Dashboard

A WordPress plugin that replaces the wp-admin dashboard with a clean, responsive grid of **shortcut blocks** and hides notices, update nags and the default WordPress widgets.

## Install

1. Download [`dist/dashwp.zip`](dist/dashwp.zip).
2. In WordPress: **Plugins → Add New → Upload Plugin**, choose `dashwp.zip`, **Install Now**, **Activate**.
3. Open **DashWP** in the admin menu.

## Create a shortcut block

| Field | Example |
|---|---|
| Name | `განცხადებები` |
| Link | `edit.php?post_type=estate_property` or a full URL such as `https://potirealtor.ge/wp-admin/edit.php?post_type=estate_property` |
| Icon | Pick from Dashicons or Font Awesome Free, or type any class (`fa-solid fa-house`) |
| Color | Any color; the text switches to black or white for contrast |

Optional: short description, open in new tab, show only to certain roles.

Edit, delete and drag-and-drop re-order blocks from the same page. Links to your own wp-admin are stored relative, so they keep working after a domain change.

## What gets hidden

On the dashboard (always): all admin notices, the welcome panel, At a Glance, Activity, WordPress News, Site Health, every plugin widget, Screen Options and Help.

Under **DashWP → Settings** (toggle):

- Update nags and update counters in the menu/toolbar (on by default)
- WordPress footer text and version (on by default)
- Admin notices on **every** admin page (off by default)
- Turn the custom dashboard off completely

## Build

```sh
./build.sh   # writes dist/dashwp.zip
```
