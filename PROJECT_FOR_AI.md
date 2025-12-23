# Symfony News App - Projekt Symfony 7.x

## Stack technologiczny
Symfony 7.x (nowa składnia atrybutów #[ORM\Entity])

Doctrine ORM + PostgreSQL (docker-compose)

Twig templates

Kontrolery MVC

text

## Struktura encji `News`
src/Entity/News.php:

id: int (primary key)

title: string

url: string

analysis: json (puste '{}')

createdAt: DateTimeImmutable (ważny getter!)

Baza: tabela news z kolumnami id, title, url, analysis, created_at

text

## Testowe dane (SQL do Postgres)
docker-compose exec database psql -U app -d app
INSERT INTO news (title, url, analysis, created_at) VALUES
('Pierwszy news', 'https://example.com/1', '{}', NOW()),
('Drugi news', 'https://example.com/2', '{}', NOW());

text

## Route + Controller
GET /news -> App\Controller\NewsController::index()

Pobiera wszystkie newsy z repo

Renderuje templates/news/index.html.twig

text

## Problem Twig (już rozwiązany)
BŁĄD: "Neither the property createdAt nor getCreatedAt() exist"
ROZWIĄZANIE: Dodać w encji:
#[ORM\Column(type: 'datetime_immutable')]
private ?\DateTimeImmutable $createdAt;

public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

cache:clear + odśwież /news

text

## Stan projektu
✅ Tabela pusta -> dodane testowe rekordy  
✅ Kontroler działa (0 newsów wyświetlało)  
✅ Encja ma `getCreatedAt()`  
✅ Szablon Twig renderuje datę `{{ item.createdAt|date('Y-m-d H:i') }}`

## Kolejne kroki (jeśli potrzebne)
php bin/console doctrine:migrations:diff (po zmianach encji)

php bin/console doctrine:migrations:migrate

php bin/console cache:clear

text

**UWAGA dla AI:** Zawsze odnoś się do tej konfiguracji. Nie zakładaj innych nazw pól/encjach. Używaj `createdAt` (z getterem), nie `created_at`.
Skopiuj ten pl
