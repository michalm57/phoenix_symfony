# User Management System (Phoenix API + Symfony Frontend)

A full-stack application composed of two cooperating systems:

1. **Backend:** Elixir + Phoenix Framework (REST API & Database).
2. **Frontend:** PHP + Symfony (Web Interface using Twig + HttpClient).

The project demonstrates a microservices-like architecture where the frontend communicates with the backend exclusively via REST API to perform CRUD operations, filtering, and sorting.

---

## 🚀 Tech Stack

### **Backend (API)**

* **Language:** Elixir 1.15
* **Framework:** Phoenix 1.7
* **Database:** PostgreSQL 15
* **Features:** Ecto for DB abstraction, JSON API, bulk data import

### **Frontend (UI)**

* **Language:** PHP 8.2
* **Framework:** Symfony 7.3.7
* **Templating:** Twig + Bootstrap 5 (CDN)
* **Communication:** Symfony HttpClient
* **Features:** Forms, validation, flash messages

### **Infrastructure**

* **Docker & Docker Compose:** Orchestration of backend, frontend, and database services

---

## 📂 Project Structure

```
.
├── backend/           # Elixir Phoenix Application
│   ├── lib/           # Business logic & Contexts
│   ├── priv/repo/     # Database migrations
│   └── ...
├── frontend/          # Symfony Application
│   ├── src/Controller # Logic bridging API and views
│   ├── src/Service/   # HTTP client wrapper for Phoenix API
│   ├── templates/     # Twig templates
│   └── ...
├── docker-compose.yml # Container orchestration
└── README.md
```

---

## 🛠️ Getting Started

**Prerequisites:** You only need Docker and Docker Compose installed. No local PHP or Elixir installation is required.

### 1. Clone the repository

```bash
git clone https://github.com/michalm57/phoenix_symfony.git
cd phoenix_symfony
```

### 2. Build and Start Containers

Run in the project root:

```bash
docker compose up --build
```

Backend may take longer on first run due to dependency compilation.

### 3. Initialize Database (Backend)

Open a second terminal:

```bash
# Create database and run migrations
docker compose exec backend mix ecto.setup
```

`ecto.setup` = `ecto.create` + `ecto.migrate`.

### 4. Install Dependencies (Frontend)

Usually automatic, but if needed:

```bash
docker compose exec frontend composer install
```

---

## 🖥️ Usage

### 🌐 Access the Application

* **Frontend (UI):** [http://localhost:8000](http://localhost:8000)
* **Backend (API JSON):** [http://localhost:4000/api/users](http://localhost:4000/api/users)

### 🎲 Generating Data (Import)

To populate the database with 100 random users:

1. Open the frontend dashboard.
2. Click **"Generate 100 random"**.
3. The system simulates fetching popular names (Polish statistics) and generates random birthdates.

### 🔍 Filtering & Sorting

List view supports:

* Filtering by: last name, gender, birthdate range
* Sorting by: name or birthdate (ASC/DESC)

---

## 📡 API Reference (Consumed by Symfony)

| Method | Endpoint       | Description                         |
| ------ | -------------- | ----------------------------------- |
| GET    | /api/users     | List users + query params           |
| GET    | /api/users/:id | Get single user                     |
| POST   | /api/users     | Create a new user                   |
| PUT    | /api/users/:id | Update existing user                |
| DELETE | /api/users/:id | Delete user                         |
| POST   | /api/import    | Trigger bulk random data generation |

---

## ℹ️ Data Sources

Data generator uses a simulated dataset based on Polish naming statistics:

* **First names:** Popular male/female names
* **Last names:** Common Polish surnames

Inspired by: dane.gov.pl (Names in Poland)

---