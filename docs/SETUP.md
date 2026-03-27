# Kompas Tour - Инструкции по запуску

## Что было сделано

Создана полная базовая структура для копии сайта Kompas Tour на Laravel 12:

### 1. База данных
- ✅ 27+ миграций со всеми необходимыми таблицами
- ✅ Справочники: страны, города, курорты, аэропорты, порты
- ✅ Туры, отели, билеты, круизы
- ✅ Система заказов и бронирований
- ✅ Система ролей и разрешений (Spatie)
- ✅ Агентства и пользователи
- ✅ Валюты и курсы
- ✅ Промо-акции и стоп-сейлы

### 2. Модели Eloquent
- ✅ 27 моделей со всеми связями (belongsTo, hasMany, belongsToMany, polymorphic)
- ✅ Мультиязычность (автоматические accessors для name_ru, name_en, name_uz)
- ✅ Правильные casts для дат, JSON, decimal
- ✅ User модель с HasRoles trait от Spatie

### 3. Аутентификация
- ✅ LoginController с проверкой активности пользователя
- ✅ RegisterController с созданием агентства + пользователя
- ✅ Middleware CheckAgencyActive
- ✅ Формы входа и регистрации

### 4. Интерфейс
- ✅ Базовый layout с навигацией (Tailwind CSS)
- ✅ Верхнее меню с логотипом и dropdown меню
- ✅ Выбор языка (RU, EN, UZ)
- ✅ Dashboard с статистикой
- ✅ Форма поиска туров с фильтрами
- ✅ Страницы для поиска отелей, билетов, экскурсий, круизов
- ✅ Страницы агентства (профиль, сотрудники)
- ✅ Страницы заявок/заказов

### 5. Контроллеры
- ✅ Auth контроллеры (Login, Register)
- ✅ DashboardController
- ✅ Search контроллеры (Tours, Hotels, Tickets, Excursions, CrossingTours, Cruises)
- ✅ AgencyController
- ✅ ClaimController

### 6. Маршруты
Все необходимые маршруты настроены в `routes/web.php`

---

## Следующие шаги для запуска

### 1. Установка зависимостей
```bash
# Убедитесь, что все пакеты установлены
composer install

# Установка npm пакетов
npm install
```

### 2. Настройка окружения
Проверьте файл `.env`:
```env
APP_NAME="Kompas Tour"
APP_URL=http://malaktour.test

DB_CONNECTION=sqlite
# или для MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=malaktour
# DB_USERNAME=root
# DB_PASSWORD=
```

### 3. Миграции уже выполнены
База данных уже создана и заполнена структурой. Если нужно пересоздать:
```bash
php artisan migrate:fresh
```

### 4. Компиляция assets
```bash
# Для разработки (с hot reload)
npm run dev

# Для production
npm run build
```

### 5. Запуск сервера
Если используете Laravel Herd, проект уже доступен по адресу:
```
http://malaktour.test
```

Если используете artisan serve:
```bash
php artisan serve
```

---

## Первый запуск

### 1. Создание тестового агентства
Перейдите на страницу регистрации:
```
http://malaktour.test/register
```

Заполните форму:
- **Данные агентства:**
  - Название агентства
  - Email
  - Телефон

- **Данные пользователя:**
  - ФИО
  - Email
  - Телефон
  - Пароль

### 2. Вход в систему
После регистрации войдите в систему:
```
http://malaktour.test/login
```

### 3. Заполнение справочников
Для работы поиска нужно заполнить справочные данные:

```bash
# Создайте seeder или вручную через tinker
php artisan tinker
```

Пример создания тестовых данных:
```php
// Валюта
$usd = \App\Models\Currency::create([
    'code' => 'USD',
    'name_en' => 'US Dollar',
    'name_ru' => 'Доллар США',
    'name_uz' => 'AQSh dollari',
    'symbol' => '$',
    'is_active' => true
]);

$uzs = \App\Models\Currency::create([
    'code' => 'UZS',
    'name_en' => 'Uzbek Som',
    'name_ru' => 'Узбекский сум',
    'name_uz' => 'O\'zbek so\'mi',
    'symbol' => 'сўм',
    'is_active' => true
]);

// Страна
$turkey = \App\Models\Country::create([
    'name_en' => 'Turkey',
    'name_ru' => 'Турция',
    'name_uz' => 'Turkiya',
    'code' => 'TUR',
    'is_active' => true
]);

// Город
$istanbul = \App\Models\City::create([
    'country_id' => $turkey->id,
    'name_en' => 'Istanbul',
    'name_ru' => 'Стамбул',
    'name_uz' => 'Istanbul',
    'is_active' => true
]);

// Курорт
$antalya = \App\Models\Resort::create([
    'country_id' => $turkey->id,
    'name_en' => 'Antalya',
    'name_ru' => 'Анталия',
    'name_uz' => 'Antalya',
    'is_active' => true
]);

// Категория отеля
$fiveStar = \App\Models\HotelCategory::create([
    'name' => '5 звезд',
    'stars' => 5,
    'is_active' => true
]);

// Отель
$hotel = \App\Models\Hotel::create([
    'resort_id' => $antalya->id,
    'hotel_category_id' => $fiveStar->id,
    'name' => 'Grand Hotel',
    'description' => 'Luxury hotel in Antalya',
    'address' => 'Antalya, Turkey',
    'rating' => 4.8,
    'is_active' => true
]);

// Тип тура
$beach = \App\Models\TourType::create([
    'name_en' => 'Beach Holiday',
    'name_ru' => 'Пляжный отдых',
    'name_uz' => 'Plyaj dam olish',
    'is_active' => true
]);

// Тип программы
$standard = \App\Models\ProgramType::create([
    'name_en' => 'Standard',
    'name_ru' => 'Стандарт',
    'name_uz' => 'Standart',
    'is_active' => true
]);

// Тип транспорта
$plane = \App\Models\TransportType::create([
    'name_en' => 'Airplane',
    'name_ru' => 'Самолет',
    'name_uz' => 'Samolyot',
    'is_active' => true
]);

// Город вылета
$tashkent = \App\Models\City::create([
    'country_id' => \App\Models\Country::where('code', 'UZB')->first()->id ?? $turkey->id,
    'name_en' => 'Tashkent',
    'name_ru' => 'Ташкент',
    'name_uz' => 'Toshkent',
    'is_active' => true
]);

// Создание тестового тура
$tour = \App\Models\Tour::create([
    'tour_type_id' => $beach->id,
    'program_type_id' => $standard->id,
    'country_id' => $turkey->id,
    'resort_id' => $antalya->id,
    'hotel_id' => $hotel->id,
    'transport_type_id' => $plane->id,
    'departure_city_id' => $tashkent->id,
    'nights' => 7,
    'price' => 1200.00,
    'currency_id' => $usd->id,
    'date_from' => now(),
    'date_to' => now()->addMonths(3),
    'adults' => 2,
    'children' => 0,
    'is_available' => true,
    'is_hot' => true,
    'instant_confirmation' => true,
    'no_stop_sale' => true
]);
```

---

## Структура проекта

### Контроллеры
```
app/Http/Controllers/
├── Auth/
│   ├── LoginController.php
│   └── RegisterController.php
├── Search/
│   ├── TourSearchController.php
│   ├── HotelSearchController.php
│   ├── TicketSearchController.php
│   ├── ExcursionSearchController.php
│   ├── CrossingTourSearchController.php
│   └── CruiseSearchController.php
├── AgencyController.php
├── ClaimController.php
└── DashboardController.php
```

### Модели
```
app/Models/
├── User.php (с HasRoles)
├── Agency.php
├── Country.php, City.php, Resort.php
├── Airport.php, Port.php
├── Hotel.php, HotelCategory.php, MealType.php
├── Tour.php, TourType.php, ProgramType.php
├── TransportType.php, Airline.php, Flight.php
├── Currency.php, CurrencyRate.php
├── CruiseCompany.php, Ship.php, CruiseRoute.php
├── Order.php, Booking.php, Tourist.php, Payment.php
├── SystemMessage.php, StopSale.php, Promotion.php
```

### Views
```
resources/views/
├── layouts/
│   └── app.blade.php (основной layout)
├── auth/
│   ├── login.blade.php
│   └── register.blade.php
├── dashboard.blade.php
├── search/
│   ├── tours/index.blade.php
│   ├── hotels/index.blade.php
│   ├── tickets/index.blade.php
│   ├── excursions/index.blade.php
│   ├── crossing-tours/index.blade.php
│   └── cruises/index.blade.php
├── agency/
│   ├── profile.blade.php
│   └── employees.blade.php
└── claims/
    ├── index.blade.php
    └── show.blade.php
```

---

## Функции для дальнейшей разработки

### Приоритет 1 (MVP)
- [ ] Реализовать логику поиска туров с фильтрами
- [ ] Реализовать вывод результатов поиска
- [ ] Добавить детальную страницу тура
- [ ] Реализовать процесс бронирования
- [ ] Добавить seeders для справочных данных
- [ ] Реализовать управление профилем агентства
- [ ] Добавить управление сотрудниками

### Приоритет 2
- [ ] Реализовать поиск отелей
- [ ] Реализовать поиск билетов
- [ ] Добавить экскурсионные туры
- [ ] Реализовать конструктор туров
- [ ] Добавить систему уведомлений
- [ ] Реализовать управление заявками
- [ ] Добавить историю платежей

### Приоритет 3
- [ ] Реализовать морские круизы
- [ ] Добавить crossing tours
- [ ] Реализовать горячие предложения (Kompas Hot)
- [ ] Добавить слайдер баннеров
- [ ] Реализовать стоп-сейл управление
- [ ] Добавить изменения расписаний
- [ ] Реализовать отчеты и статистику

### Интеграции
- [ ] API синхронизация с туроператорами
- [ ] Платежные системы (Payme, Click, Uzcard)
- [ ] Email уведомления
- [ ] SMS уведомления
- [ ] Telegram Bot для уведомлений

### Оптимизация
- [ ] Кеширование справочников
- [ ] Индексы для поисковых запросов
- [ ] Очереди для тяжелых операций
- [ ] CDN для статики
- [ ] Оптимизация изображений

---

## Технологии

- **Laravel**: 12.49.0
- **PHP**: 8.3.29
- **Database**: SQLite (можно переключить на MySQL)
- **Frontend**: Tailwind CSS 4.1.18
- **Packages**:
  - spatie/laravel-permission: 6.24.0
  - intervention/image-laravel: 1.5.6

---

## Полезные команды

```bash
# Просмотр маршрутов
php artisan route:list

# Просмотр миграций
php artisan migrate:status

# Создание seeder
php artisan make:seeder CountriesSeeder

# Запуск seeder
php artisan db:seed --class=CountriesSeeder

# Очистка кеша
php artisan optimize:clear

# Создание нового контроллера
php artisan make:controller Admin/HotelController --resource

# Создание middleware
php artisan make:middleware CheckRole

# Tinker для работы с базой
php artisan tinker
```

---

## Доступ к проекту

- **URL**: http://malaktour.test
- **Login Page**: http://malaktour.test/login
- **Register Page**: http://malaktour.test/register
- **Dashboard**: http://malaktour.test/dashboard (после входа)
- **Tour Search**: http://malaktour.test/search/tours

---

## Поддержка

Если возникнут вопросы или проблемы:
1. Проверьте логи в `storage/logs/laravel.log`
2. Убедитесь, что все миграции выполнены: `php artisan migrate:status`
3. Проверьте права доступа к `storage` и `bootstrap/cache`
4. Очистите кеш: `php artisan optimize:clear`

---

## Следующая сессия разработки

Рекомендую в следующей сессии сосредоточиться на:
1. Создание seeders для заполнения тестовых данных
2. Реализация логики поиска туров
3. Добавление пагинации результатов
4. Реализация детальной страницы тура
5. Начало работы над процессом бронирования

Удачи в разработке! 🚀
