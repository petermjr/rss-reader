## How It Works

### Asynchronous Feed Processing

- The backend processes RSS feed updates asynchronously when you add a new feed or manually refresh a feed from the UI.

---

## Getting Started

### Prerequisites

- [Docker](https://www.docker.com/products/docker-desktop)
- [Docker Compose](https://docs.docker.com/compose/)

### Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/rss-reader.git
   cd rss-reader
   ```

2. **Start the application with Docker Compose:**
   ```bash
   docker compose up --build
   ```
   - The backend API will be available at [http://localhost:8080](http://localhost:8080)
   - The frontend (React) will be available at [http://localhost:3000](http://localhost:3000)

---

When testing the feed functionality, you can use these example feeds:
```
https://rss.nytimes.com/services/xml/rss/nyt/World.xml
https://feeds.megaphone.fm/GLT1412515089
```