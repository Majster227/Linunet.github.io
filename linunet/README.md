# ◉ LINUNET
## The network that connects people who think different.

Built with PHP + SQLite. No dependencies. Runs on Linux.

---

## QUICK START

### 1. Install PHP (Linux Mint / Ubuntu / Debian)
```bash
sudo apt update
sudo apt install php php-sqlite3 php-gd
```

### 2. Create default avatar
```bash
cd ~/linunet
python3 -c "
from PIL import Image, ImageDraw
img = Image.new('RGB', (100,100), (42,42,28))
d = ImageDraw.Draw(img)
d.ellipse([32,18,68,54], fill=(200,168,60))
d.ellipse([16,62,84,104], fill=(200,168,60))
img.save('uploads/avatars/default.png')
print('OK')
"
```
If Pillow not installed: `sudo apt install python3-pil`

### 3. Run the server
```bash
cd ~/linunet
php -S localhost:8080
```

### 4. Open in browser
```
http://localhost:8080
```

### 5. Register your account, then make yourself admin
```bash
php make_admin.php yourlogin
```

---

## DEFAULT ADMIN ACCOUNT
- **Login:** `admin`
- **Password:** `Linunet2024!`
- **Change it immediately in Settings!**

---

## FILE STRUCTURE
```
linunet/
├── index.php          – Login page
├── register.php       – Registration
├── home.php           – Main feed
├── profile.php        – User profile
├── friends.php        – Friends management
├── friend_action.php  – Friend request handler
├── post.php           – Post + comments
├── messages.php       – Private messages
├── search.php         – User search
├── members.php        – All members directory
├── settings.php       – Account settings
├── admin.php          – Admin panel
├── report.php         – Report content
├── about.php          – About page
├── contact.php        – Contact form
├── logout.php         – Log out
├── config.php         – DB + constants
├── auth.php           – Auth functions
├── make_admin.php     – CLI admin tool
├── tpl_header.php     – Page header
├── tpl_footer.php     – Page footer
├── style.css          – All styles
├── data/
│   ├── linunet.db     – SQLite database (auto-created)
│   └── contact.txt    – Contact form messages
└── uploads/
    ├── avatars/       – Profile pictures
    └── posts/         – Post images
```

---

## FEATURES
- ✓ Registration & login
- ✓ Profile with avatar, bio, favorite OS, interests, birthdate
- ✓ Post feed (text + images + links)
- ✓ Post privacy (public / friends only)
- ✓ Friends system (requests, accept, decline)
- ✓ Private messages
- ✓ Comments on posts
- ✓ User search
- ✓ Members directory
- ✓ Suggested friends
- ✓ Report system
- ✓ Admin panel
- ✓ About & Contact pages
- ✓ Delete own posts & account
- ✓ Retro early-web design

---

*Linunet – built with passion on Linux* 🐧
