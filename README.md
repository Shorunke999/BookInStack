# BookinStack

BookinStack is a **booking and payment platform** built with Laravel, designed to handle real-world transactional workflows for service providers and event organizers.

It combines flexible booking systems, payment infrastructure, and operational tools into a single scalable backend.

---

## 🌍 Live Usage / Demos

Here are examples of BookinStack being used in real scenarios:

- 🔗 Widget Integration Example 1: https://chipper-elf-4baf92-netlify-app
- 🔗 Widget Integration Example 2: https://curious-dango-13d118.netlify.app

These demonstrate how BookinStack can be embedded into external websites using a lightweight widget to handle bookings and payments.

- 🚀 Main Application (Demo):
  https://bookinstack-main-wuhnit.free.laravel.cloud

---

## 🚀 Core Features

- 🗓 **Three booking modes**
  - Appointments (time-based scheduling)
  - Tickets (event-based access)
  - Reservations (capacity-based bookings)

- 💳 **Integrated payments (Paystack)**
  - Automatic **configurable revenue split**
  - Secure and consistent transaction handling

- 🎟 **QR Code Ticketing**
  - Unique QR codes per booking
  - Camera-based **staff check-in system**

- 💬 **WhatsApp Price Negotiation**
  - Dynamic pricing conversations
  - Generate **payment links directly from chat flow**

- 🔗 **Shareable Payment Links**
  - Sell services outside the platform
  - Supports off-platform conversions

- 📊 **Business Dashboard**
  - Booking management
  - Staff accounts & permissions
  - Attendance tracking

- 🌐 **Embeddable Widget**
  - Plug into any website with **3 lines of code**
  - Customizable UI per business

- 📧 **Automated Emails**
  - Branded confirmations
  - PDF ticket attachments

- ⚙️ **Per-Business Configuration**
  - Booking hours
  - Pricing logic
  - Widget appearance

---

## 🧠 System Design Focus

This project was built with a strong emphasis on:

- Clean and maintainable architecture
- Reliable **transactional workflows**
- Scalable data modeling
- API-first design
- Separation of concerns across services

---

## 🏗 Tech Stack

- **Backend:** Laravel (PHP)
- **Database:** MySQL
- **Frontend:** Blade 
- **Payments:** Paystack API
- **Queue System:** Laravel Queues (for async jobs)
- **Other:** REST APIs, third-party integrations

---

## 🔌 Key Backend Concepts Implemented

- RESTful API architecture
- Background job processing (queues)
- Payment lifecycle handling (initiation → verification → settlement)
- Role-based access control (RBAC)
- Event-driven flows (bookings, payments, notifications)
- External API integrations

---

## 🗄 Database

- Structured relational schema with focus on:
  - bookings
  - users & roles
  - transactions
  - services/events
- Includes **migrations and seeders** for:
  - rapid environment setup
  - consistent test data
  - reproducible development workflows

---

## ⚙️ Installation

```bash
git clone https://github.com/Shorunke999/BookInStack.git
cd bookstack

composer install
cp .env.example .env
php artisan key:generate

# Configure your database in .env

php artisan migrate --seed

php artisan serve
