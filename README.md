# Coding Master Forum

একটি আধুনিক, হালকা-ওজনের Forum সাইট যেটা তৈরি করা হয়েছে PHP 8+ এবং SQLite দিয়ে।  
পুরো প্রোজেক্টের স্ট্রাকচার clean MVC প্যাটার্ন অনুসরণ করে, ভবিষ্যতে সহজেই update/modify করা যাবে।

## 🚀 Features

- আধুনিক ফোল্ডার স্ট্রাকচার (MVC + Core + Routes)
- SQLite Database (serverless, lightweight)
- Installer (install.php) → Admin user + database setup
- User Authentication (login/register)
- Forum, Threads, Posts
- Private Messages
- Notifications system
- Admin Panel (users, forums, settings)
- Multi-language support (lang/en.php, lang/bn.php)
- Static assets (CSS, JS, images, uploads)
- Logs + Cache system
- Responsive design
- API endpoints

## 📂 Folder Structure

/my_forum
│── index.php              # Entry point
│── install.php            # Installer
│── config.php             # Global config
│── .htaccess              # Rewrite rules (Apache)
│── README.md              # Documentation
│
├── core/                  # Core system files
│    ├── Database.php
│    ├── Auth.php
│    ├── Functions.php
│    ├── Router.php
│    └── Middleware.php
│
├── models/                # Database Models
│    ├── User.php
│    ├── Forum.php
│    ├── Thread.php
│    ├── Post.php
│    ├── Message.php
│    └── Notification.php
│
├── controllers/           # Controllers
│    ├── HomeController.php
│    ├── AuthController.php
│    ├── ForumController.php
│    ├── UserController.php
│    ├── MessageController.php
│    ├── AdminController.php
│    └── ApiController.php
│
├── views/                 # Views (templates)
│    ├── header.php
│    ├── footer.php
│    ├── home.php
│    ├── login.php
│    ├── register.php
│    ├── error.php
│    ├── forum_list.php
│    ├── thread_view.php
│    ├── thread_create.php
│    ├── thread_edit.php
│    ├── post_edit.php
│    ├── search.php
│    ├── user/
│    │     ├── dashboard.php
│    │     ├── profile.php
│    │     ├── settings.php
│    │     ├── messages.php
│    │     ├── conversation.php
│    │     └── notifications.php
│    └── admin/
│          ├── dashboard.php
│          ├── users.php
│          ├── user_edit.php
│          ├── forums.php
│          ├── forum_create.php
│          ├── forum_edit.php
│          ├── threads.php
│          ├── posts.php
│          └── settings.php
│
├── public/                # Static files
│    ├── index.php         # Front controller
│    ├── css/
│    │     └── style.css
│    ├── js/
│    │     └── app.js
│    ├── images/
│    │     ├── logo.png
│    │     └── default-avatar.png
│    └── uploads/
│          ├── avatars/
│          └── attachments/
│
├── lang/                  # Translations
│    ├── en.php
│    └── bn.php
│
├── routes/                # Routes
│    ├── web.php
│    ├── admin.php
│    └── api.php
│
├── storage/               # App storage
│    ├── forum.sqlite      # Database file
│    ├── installed.lock    # Install lock file
│    ├── logs/
│    │     └── app.log
│    └── cache/
│          └── index.cache
│
└── docs/                  # Documentation
     ├── install.md
     └── structure.md

## ⚙️ Installation Guide

1. Download & Extract  
   ZIP ফাইল extract করে তোমার সার্ভারে রাখো।  

2. Permissions  
   - storage/ ফোল্ডার writeable করতে হবে (logs/cache/db file এর জন্য)  
   - public/uploads/ ফোল্ডার writeable করতে হবে (avatars/attachments এর জন্য)  

3. Run Installer  
   Browser এ গিয়ে ওপেন করো:  
   
   http://localhost/my_forum/install.php
   
   - এখানে Admin Username, Email, Password দিয়ে সাবমিট করো।  
   - forum.sqlite ডাটাবেজ auto তৈরি হবে।  
   - installed.lock তৈরি হয়ে গেলে আবার install.php কাজ করবে না।  

4. Login  
   - index.php → login page এ গিয়ে তোমার admin info দিয়ে লগইন করো।  

## 🔑 Default Admin

Installer এ তুমি যে Admin username/email/password দেবে, সেটাই future login এর জন্য ব্যবহার হবে।  

## 🛠 Requirements

- PHP 8.0+
- SQLite (enabled in PHP)
- Apache/Nginx (Apache হলে .htaccess লাগবে)
- mod_rewrite enabled (for clean URLs)

## 📜 License

Project Name: Coding Master  
Author: Minaz Ahmad  
Email: minazahmad@gmail.com  

Copyright (c) 2025 Minaz Ahmad

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

- The above copyright notice and this permission notice shall be included in
  all copies or substantial portions of the Software.
- The Software is provided "as is", without warranty of any kind, express or
  implied, including but not limited to the warranties of merchantability,
  fitness for a particular purpose and noninfringement.

👉 সহজভাবে বললে, তুমি এই প্রোজেক্ট ফ্রি ব্যবহার করতে পারো, modify করতে পারো, distribute করতে পারো, কিন্তু কোনো গ্যারান্টি দেওয়া হচ্ছে না।

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📞 Support

যদি কোনো সমস্যা থাকে বা সাহায্য প্রয়োজন হয়, তাহলে আমার সাথে যোগাযোগ করতে পারেন:

- Email: minazahmad@gmail.com
- GitHub: [Issue তৈরি করুন](https://github.com/yourusername/coding-master-forum/issues)

## 🔄 Future Development

- নতুন Model/Controller/View আলাদা ফোল্ডারে রাখতে পারবে।  
- Routes যোগ করতে হবে → routes/web.php বা routes/admin.php তে।  
- Multi-language সাপোর্টের জন্য lang/ ফোল্ডার expand করতে পারবে।  
- Static files → public/ ফোল্ডারে।  
- Cache & Logs → storage/ ফোল্ডারে।  

---

**Happy Coding!** 🚀