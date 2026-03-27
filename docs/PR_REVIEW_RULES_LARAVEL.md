# Laravel Backend - PR Review Rules & Standards

**Version:** 1.0
**Last Updated:** 2026-03-17
**Purpose:** Comprehensive code review guidelines for AI PR review tool and developers

**This is Engineering Law, Not Guidance.**

---

## Table of Contents

1. [Architecture & Layer Rules](#1-architecture--layer-rules)
2. [Domain Layer Rules](#2-domain-layer-rules)
3. [Repository Pattern Rules](#3-repository-pattern-rules)
4. [Action (Use Case) Rules](#4-action-use-case-rules)
5. [Error Handling Rules](#5-error-handling-rules)
6. [Code Style & Formatting Rules](#6-code-style--formatting-rules)
7. [Naming Conventions](#7-naming-conventions)
8. [Money & Currency Rules](#8-money--currency-rules)
9. [Testing Requirements](#9-testing-requirements)
10. [Documentation Standards](#10-documentation-standards)
11. [Security & Safety Rules](#11-security--safety-rules)
12. [General Best Practices](#12-general-best-practices)
13. [Generated Code & Artifacts](#13-generated-code--artifacts)
14. [Pull Request Standards](#14-pull-request-standards)
15. [Dependency Management](#15-dependency-management)
16. [AI Review Tool Implementation Guide](#16-ai-review-tool-implementation-guide)
17. [Database Transaction Pattern](#17-database-transaction-pattern)
18. [Domain Events & Queue Pattern](#18-domain-events--queue-pattern)
19. [Anti-Patterns Consolidated](#19-anti-patterns-consolidated)
20. [Observability & Logging](#20-observability--logging)
21. [Performance Optimization](#21-performance-optimization)
22. [CQRS Pattern Details](#22-cqrs-pattern-details)
23. [Deployment & Release](#23-deployment--release)

---

## Authority & Scope

**This document defines MANDATORY engineering rules, not suggestions.**

### Enforcement Policy

- **[CRITICAL]** violations **BLOCK MERGE** - No exceptions without explicit Tech Lead approval
- **[WARNING]** violations **SHOULD BE FIXED** - Can merge with justification
- **[SUGGESTION]** items are **OPTIONAL** - Nice to have improvements

### AI Review Findings Are Binding

AI review findings marked as **[CRITICAL]** are **NOT OPTIONAL**. They represent:
- Architectural violations that compromise system integrity
- Bugs or security vulnerabilities
- Code that violates established patterns and standards

**Exception Process:**
1. Developer believes AI finding is incorrect -> Tag Tech Lead in PR comments
2. Tech Lead reviews and either:
   - Confirms violation -> Developer must fix
   - Overrides with written justification -> Merge approved

### This is Engineering Constitution

These rules are derived from:
- Clean Architecture principles (Robert C. Martin)
- Domain-Driven Design (Eric Evans)
- Production incidents and lessons learned
- Team architectural decisions
- Laravel best practices distilled from real-world large-scale applications

**Violations create technical debt and production risk.**

---

## Severity Levels

- **[CRITICAL]** - Must be fixed before merge. Violates architecture principles or causes bugs.
- **[WARNING]** - Should be fixed. May cause maintenance issues or technical debt.
- **[SUGGESTION]** - Nice to have. Improves code quality but not blocking.

---

## 1. Architecture & Layer Rules

### 1.1 [CRITICAL] Follow the Mandatory Flow

**Rule:** All data modification MUST follow this exact flow:
```
Controller -> Action -> Domain (business logic) -> Repository (persist) -> [within DB::transaction]
```

**Why:** Ensures clean separation of concerns and testability.

**Bad:**
```php
// Controller directly calls Eloquent model
class OrderController extends Controller
{
    public function activate(Request $request, string $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $order->status = 'active'; // Direct field modification
        $order->save();            // Controller persists
        return response()->json($order);
    }
}
```

**Good:**
```php
// Controller -> Action -> Domain -> Repository
class OrderController extends Controller
{
    public function __construct(
        private readonly ActivateOrderAction $activateOrder,
    ) {}

    public function activate(ActivateOrderRequest $request, string $id): JsonResponse
    {
        $result = $this->activateOrder->execute(
            new ActivateOrderInput(orderId: $id)
        );

        return response()->json(OrderResource::make($result));
    }
}

// Action orchestrates
final class ActivateOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly Clock $clock,
    ) {}

    public function execute(ActivateOrderInput $input): OrderData
    {
        return DB::transaction(function () use ($input) {
            $order = $this->orderRepo->findOrFail($input->orderId);

            // Domain method (business logic)
            $order->activate($this->clock->now());

            // Repository persists
            $this->orderRepo->save($order);

            return OrderData::fromDomain($order);
        });
    }
}
```

**Check for:**
- Actions MUST orchestrate domain calls and repository persistence
- Controllers MUST NOT call Eloquent models or repositories directly
- Domain models MUST NOT persist themselves (no `$this->save()` in domain methods)
- All writes MUST happen inside `DB::transaction()`

---

### 1.2 [CRITICAL] Never Violate Layer Dependencies

**Rule:** Dependencies MUST flow inward only: `Outer -> Inner`
```
[Controllers/Middleware] -> [Actions] -> [Domain]
```

**Domain layer:**
- NEVER import: Eloquent base model, facades, request objects, framework classes
- NEVER call: `DB::`, `Cache::`, `Log::`, `Http::`, `Queue::`, or any facade
- ONLY depend on: plain PHP, value objects, interfaces defined in the domain

**Action layer:**
- NEVER import: concrete repository implementations, other actions, Eloquent models directly
- ONLY import: repository interfaces (contracts), domain models, DTOs

**Examples to catch:**

```php
// BAD: Domain importing infrastructure
namespace App\Domain\Order;

use Illuminate\Support\Facades\DB;   // NEVER in domain
use Illuminate\Support\Facades\Log;  // NEVER in domain

class Order
{
    public function activate(): void
    {
        Log::info('Activating order'); // Logging in domain
        $this->status = Status::Active;
        DB::table('orders')->where('id', $this->id)->update(['status' => 'active']); // DB access in domain
    }
}
```

```php
// BAD: Action importing concrete repository
namespace App\Actions\Order;

use App\Repositories\Eloquent\EloquentOrderRepository; // NEVER import concrete impl

// GOOD: Action importing contract
use App\Contracts\Repositories\OrderRepositoryInterface; // Interface only
```

---

### 1.3 [WARNING] No Bypass of Repository Layer

**Rule:** ALL data access MUST go through repository interfaces. Never access DB directly from actions or domain.

**Check for:**
- Direct `DB::` facade usage in actions: `DB::table(...)`, `DB::select(...)`
- Eloquent static calls in actions: `Order::where(...)`, `Order::find(...)`
- Domain models that call `$this->save()` or any persistence method

---

### 1.4 [WARNING] Action Independence

**Rule:** Actions MUST be independent. Never have one action import another action.

**Why:** Actions represent independent business operations. Coupling creates circular dependencies and makes testing harder.

**Bad:**
```php
namespace App\Actions\Order;

use App\Actions\Customer\ValidateCustomerAction; // Action importing action

final class CreateOrderAction
{
    public function __construct(
        private readonly ValidateCustomerAction $validateCustomer, // Coupling
    ) {}
}
```

**Good:**
```php
namespace App\Actions\Order;

use App\Contracts\Services\CustomerValidatorInterface; // Shared interface

final class CreateOrderAction
{
    public function __construct(
        private readonly CustomerValidatorInterface $customerValidator,
    ) {}
}
```

---

## 2. Domain Layer Rules

### 2.1 [CRITICAL] Domain Purity

**Rule:** Domain methods MUST be pure business logic with ZERO infrastructure dependencies.

**Check for:**
- Facade calls (`DB::`, `Cache::`, `Log::`, `Event::`) in domain classes
- `Request` objects passed to domain methods
- Eloquent methods (`save()`, `delete()`, `refresh()`) called inside domain logic
- Imports from `Illuminate\` namespace in domain layer

**Bad:**
```php
namespace App\Domain\Order;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class Order
{
    public function activate(Request $request): void
    {
        Log::info("Activating order {$this->id}");  // Logging in domain
        $this->status = Status::Active;
        $this->save();  // Persistence in domain
    }
}
```

**Good:**
```php
namespace App\Domain\Order;

use App\Domain\Order\Events\OrderActivated;
use DateTimeImmutable;

class Order
{
    public function activate(DateTimeImmutable $now): void
    {
        if ($this->status !== Status::Confirmed) {
            throw new InvalidTransitionException(
                from: $this->status,
                to: Status::Active,
            );
        }

        $this->status = Status::Active;
        $this->activatedAt = $now;
        $this->recordChange('status', $this->status);
        $this->raise(new OrderActivated(
            orderId: $this->id,
            customerId: $this->customerId,
            activatedAt: $now,
        ));
    }
}
```

---

### 2.2 [CRITICAL] Encapsulated Fields with Intention-Revealing Methods

**Rule:** Domain state changes MUST go through intention-revealing methods. Never allow direct property assignment from outside the aggregate.

**Bad:**
```php
class Order
{
    public string $status;  // Public property
    public string $total;   // Direct access
}

// Usage:
$order->status = 'active';  // Direct mutation
```

**Good:**
```php
class Order
{
    private OrderId $id;
    private Status $status;
    private Money $totalAmount;
    private DateTimeImmutable $activatedAt;
    private array $changes = [];
    private array $events = [];

    public function status(): Status
    {
        return $this->status;
    }

    public function activate(DateTimeImmutable $now): void
    {
        if ($this->status !== Status::Confirmed) {
            throw new InvalidTransitionException(
                from: $this->status,
                to: Status::Active,
            );
        }

        $this->status = Status::Active;
        $this->activatedAt = $now;
        $this->recordChange('status', $this->status);
    }
}
```

---

### 2.3 [CRITICAL] Use Change Tracking

**Rule:** Aggregates MUST track field changes for optimized persistence and audit trails.

**Why:** Repositories need to know which fields changed to build optimized UPDATE queries (partial updates rather than full-row overwrites).

**Check for:**
- Aggregates without a change tracking mechanism
- Field mutations without `$this->recordChange(...)` or equivalent
- Direct field updates that bypass change tracking

**Implementation:**

```php
namespace App\Domain\Shared;

trait TracksChanges
{
    private array $changedFields = [];

    protected function recordChange(string $field, mixed $value): void
    {
        $this->changedFields[$field] = $value;
    }

    public function isDirty(string $field): bool
    {
        return array_key_exists($field, $this->changedFields);
    }

    public function dirtyFields(): array
    {
        return array_keys($this->changedFields);
    }

    public function changes(): array
    {
        return $this->changedFields;
    }

    public function clearChanges(): void
    {
        $this->changedFields = [];
    }
}
```

**Usage in Aggregate:**

```php
class Order
{
    use TracksChanges;

    public function activate(DateTimeImmutable $now): void
    {
        $this->status = Status::Active;
        $this->activatedAt = $now;
        $this->recordChange('status', $this->status->value);
        $this->recordChange('activated_at', $this->activatedAt);
    }
}
```

**Repository Integration:**

```php
final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function save(Order $order): void
    {
        $changes = $order->changes();

        if (empty($changes)) {
            return; // Nothing to persist
        }

        OrderModel::where('id', $order->id()->toString())
            ->update($changes);

        $order->clearChanges();
    }
}
```

**Change Tracking in Tests:**

```php
public function test_activate_tracks_status_change(): void
{
    $order = OrderFactory::createConfirmed();
    $order->activate(new DateTimeImmutable('2026-01-15 10:30:00'));

    $this->assertTrue($order->isDirty('status'));
    $this->assertTrue($order->isDirty('activated_at'));
    $this->assertFalse($order->isDirty('total_amount')); // Unchanged
}
```

**Anti-Patterns:**

```php
// Missing recordChange() - CRITICAL
public function activate(DateTimeImmutable $now): void
{
    $this->status = Status::Active; // Direct assignment
    // No recordChange() call - repo won't detect this
}

// Forgetting to clear changes
public function save(Order $order): void
{
    OrderModel::where('id', $order->id()->toString())
        ->update($order->changes());
    // Missing: $order->clearChanges()
    // Next save will include stale dirty fields
}
```

**PR Review Checklist:**
- [ ] Aggregate uses `TracksChanges` trait (or equivalent)
- [ ] Every state mutation calls `$this->recordChange()`
- [ ] Repository checks `$order->changes()` before persisting
- [ ] `clearChanges()` called after successful persistence
- [ ] Domain tests assert `isDirty()` for modified fields
- [ ] Domain tests assert `!isDirty()` for unmodified fields

---

### 2.4 [CRITICAL] Raise Domain Events

**Rule:** Significant state changes MUST raise domain events.

**Why:** Events enable async workflows, notifications, audit trails, and cross-boundary communication.

**Bad:**
```php
public function activate(DateTimeImmutable $now): void
{
    $this->status = Status::Active;
    $this->recordChange('status', $this->status->value);
    // No event raised
}
```

**Good:**
```php
public function activate(DateTimeImmutable $now): void
{
    $this->status = Status::Active;
    $this->activatedAt = $now;
    $this->recordChange('status', $this->status->value);

    $this->raise(new OrderActivated(
        orderId: $this->id,
        customerId: $this->customerId,
        activatedAt: $now,
    ));
}
```

**Event Trait:**

```php
trait RaisesEvents
{
    private array $pendingEvents = [];

    protected function raise(object $event): void
    {
        $this->pendingEvents[] = $event;
    }

    public function pullEvents(): array
    {
        $events = $this->pendingEvents;
        $this->pendingEvents = [];
        return $events;
    }
}
```

---

## 3. Repository Pattern Rules

### 3.1 [CRITICAL] Repository Abstracts Persistence

**Rule:** Repository implementations MUST hide all persistence details behind interfaces. Domain and actions NEVER know about Eloquent, Query Builder, or database structure.

**Bad:**
```php
// Action using Eloquent directly
final class ActivateOrderAction
{
    public function execute(ActivateOrderInput $input): void
    {
        $model = \App\Models\Order::findOrFail($input->orderId);
        $model->status = 'active';
        $model->save(); // Leaking Eloquent into action layer
    }
}
```

**Good:**
```php
// Interface in contracts
namespace App\Contracts\Repositories;

interface OrderRepositoryInterface
{
    public function findOrFail(string $orderId): Order;
    public function save(Order $order): void;
}

// Eloquent implementation
namespace App\Repositories\Eloquent;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function findOrFail(string $orderId): Order
    {
        $model = OrderModel::findOrFail($orderId);

        // Repository handles mapping: Eloquent model -> Domain aggregate
        return Order::reconstitute(
            id: new OrderId($model->id),
            customerId: new CustomerId($model->customer_id),
            status: Status::from($model->status),
            totalAmount: Money::fromCents($model->total_amount_cents),
            createdAt: new DateTimeImmutable($model->created_at),
        );
    }

    public function save(Order $order): void
    {
        $changes = $order->changes();

        if (empty($changes)) {
            return;
        }

        OrderModel::where('id', $order->id()->toString())
            ->update($changes);

        $order->clearChanges();
    }
}
```

---

### 3.2 [CRITICAL] Repository Handles Mapping

**Rule:** Repositories MUST perform all mapping between persistence models (Eloquent) and domain aggregates.

**Why:** Keeps domain decoupled from DB structure. Domain never sees `$model->column_name`.

**Check for:**
- `findOrFail()` methods returning Eloquent model instances
- Domain aggregates with Eloquent casts, `$fillable`, `$casts`, or `$table` properties
- Actions performing mapping from Eloquent models to domain objects

---

### 3.3 [WARNING] Repositories Are Dumb Mappers

**Rule:** Repositories MUST NOT contain business logic. They only map data between DB and domain.

**Bad:**
```php
final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function save(Order $order): void
    {
        // Business logic in repo
        if ($order->status() === Status::Pending && $order->totalAmount()->greaterThan(Money::fromCents(100000))) {
            $order->applyDiscount(0.1);
        }

        OrderModel::where('id', $order->id()->toString())
            ->update($order->changes());
    }
}
```

**Good:**
```php
final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function save(Order $order): void
    {
        // Pure mapping logic
        $changes = $order->changes();

        if (empty($changes)) {
            return;
        }

        OrderModel::where('id', $order->id()->toString())
            ->update($changes);

        $order->clearChanges();
    }
}
```

---

### 3.4 [WARNING] Bind Interfaces in Service Provider

**Rule:** All repository implementations MUST be bound to their interfaces in a service provider.

```php
// AppServiceProvider or a dedicated RepositoryServiceProvider
public function register(): void
{
    $this->app->bind(
        OrderRepositoryInterface::class,
        EloquentOrderRepository::class,
    );
}
```

---

## 4. Action (Use Case) Rules

### 4.1 [CRITICAL] Actions Orchestrate, Domain Decides

**Rule:** Actions orchestrate: load aggregates, call domain methods, persist via repository. They NEVER contain business logic themselves.

**Bad:**
```php
final class ActivateOrderAction
{
    public function execute(ActivateOrderInput $input): void
    {
        $order = $this->orderRepo->findOrFail($input->orderId);

        // Business logic leaked into action
        if ($order->status() !== Status::Confirmed) {
            throw new \DomainException('Cannot activate');
        }

        $order->setStatus(Status::Active);
        $order->setActivatedAt(now());
        $this->orderRepo->save($order);
    }
}
```

**Good:**
```php
final class ActivateOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly Clock $clock,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function execute(ActivateOrderInput $input): OrderData
    {
        return DB::transaction(function () use ($input) {
            $order = $this->orderRepo->findOrFail($input->orderId);

            // Domain decides
            $order->activate($this->clock->now());

            // Action persists
            $this->orderRepo->save($order);

            // Action dispatches events
            foreach ($order->pullEvents() as $event) {
                $this->dispatcher->dispatch($event);
            }

            return OrderData::fromDomain($order);
        });
    }
}
```

---

### 4.2 [WARNING] One Action Per Class

**Rule:** Each action MUST be its own class in a dedicated namespace.

**Structure:**
```
app/Actions/
  Order/
    ActivateOrderAction.php
    CreateOrderAction.php
    CancelOrderAction.php
  Payment/
    ProcessPaymentAction.php
```

---

### 4.3 [WARNING] Constructor Injection Only

**Rule:** Actions MUST receive all dependencies via constructor injection. No service location, no facades inside actions.

**Bad:**
```php
final class ActivateOrderAction
{
    public function execute(ActivateOrderInput $input): void
    {
        $order = app(OrderRepositoryInterface::class)->findOrFail($input->orderId); // Service location
        Cache::forget("order:{$input->orderId}");                                   // Facade
    }
}
```

**Good:**
```php
final class ActivateOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly CacheInterface $cache,
        private readonly Clock $clock,
    ) {}

    public function execute(ActivateOrderInput $input): OrderData
    {
        // Dependencies injected, testable
    }
}
```

---

### 4.4 [SUGGESTION] Use Typed Input/Output DTOs

**Rule:** Actions SHOULD accept typed input DTOs and return typed output DTOs. Never pass `Request` objects or return Eloquent models.

**Bad:**
```php
public function execute(Request $request): Model // Framework types leak
```

**Good:**
```php
final readonly class ActivateOrderInput
{
    public function __construct(
        public string $orderId,
    ) {}
}

final readonly class OrderData
{
    public function __construct(
        public string $id,
        public string $status,
        public string $totalAmount,
        public string $activatedAt,
    ) {}

    public static function fromDomain(Order $order): self
    {
        return new self(
            id: $order->id()->toString(),
            status: $order->status()->value,
            totalAmount: $order->totalAmount()->formatted(),
            activatedAt: $order->activatedAt()?->format('c'),
        );
    }
}
```

---

## 5. Error Handling Rules

### 5.1 [CRITICAL] Three Types of Errors

**Rule:** Distinguish between Domain, Application, and Infrastructure errors.

| Type | Layer | Retryable | HTTP Status | Example |
|------|-------|-----------|-------------|---------|
| Domain | Domain | No | 422 | Invalid state transition |
| Application | Action | No | 400/404 | Validation failed, Not found |
| Infrastructure | Adapter | Maybe | 500/503 | DB timeout, API failure |

**Domain Exceptions:**
```php
namespace App\Domain\Order\Exceptions;

final class InvalidTransitionException extends \DomainException
{
    public function __construct(
        public readonly Status $from,
        public readonly Status $to,
    ) {
        parent::__construct(
            "Cannot transition from {$from->value} to {$to->value}"
        );
    }
}
```

**Application Exceptions:**
```php
namespace App\Exceptions;

final class OrderNotFoundException extends \RuntimeException
{
    public static function withId(string $id): self
    {
        return new self("Order not found: {$id}");
    }
}
```

---

### 5.2 [CRITICAL] Don't Swallow Domain Exceptions

**Rule:** When calling domain methods, let domain exceptions propagate. Don't wrap them in generic exceptions.

**Bad:**
```php
try {
    $order->activate($now);
} catch (InvalidTransitionException $e) {
    throw new \RuntimeException('Failed to activate: ' . $e->getMessage()); // Loses type info
}
```

**Good:**
```php
// Let it propagate - the exception handler will map it to the correct HTTP response
$order->activate($now);
```

---

### 5.3 [WARNING] Map Exceptions in Handler

**Rule:** Use Laravel's exception handler to map domain/application exceptions to HTTP responses. Keep mapping out of controllers.

```php
// app/Exceptions/Handler.php or bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (InvalidTransitionException $e) {
        return response()->json([
            'error' => 'invalid_transition',
            'message' => $e->getMessage(),
            'from' => $e->from->value,
            'to' => $e->to->value,
        ], 422);
    });

    $exceptions->render(function (OrderNotFoundException $e) {
        return response()->json([
            'error' => 'not_found',
            'message' => $e->getMessage(),
        ], 404);
    });
})
```

---

### 5.4 [CRITICAL] Validate in Form Request, Not Domain

**Rule:** Input format validation (required, string length, email format) goes in Form Requests. Business rule validation (state transitions, invariants) stays in domain.

**Bad:**
```php
// Domain validating input format
class Order
{
    public function activate(string $userId): void
    {
        if (empty($userId)) {                    // Input validation in domain
            throw new \InvalidArgumentException('User ID required');
        }
        if (strlen($userId) > 36) {              // Input validation in domain
            throw new \InvalidArgumentException('User ID too long');
        }
        // ...
    }
}
```

**Good:**
```php
// Form Request validates input format
final class ActivateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'max:36'],
        ];
    }
}

// Domain validates business rules
class Order
{
    public function activate(DateTimeImmutable $now): void
    {
        if ($this->status !== Status::Confirmed) {
            throw new InvalidTransitionException(
                from: $this->status,
                to: Status::Active,
            );
        }
        // ...
    }
}
```

---

## 6. Code Style & Formatting Rules

### 6.1 [WARNING] Strict Types Declaration

**Rule:** Every PHP file MUST declare strict types.

```php
<?php

declare(strict_types=1);

namespace App\Domain\Order;
```

---

### 6.2 [WARNING] Final by Default

**Rule:** All classes MUST be `final` unless explicitly designed for inheritance.

**Why:** Prevents unintended inheritance, makes refactoring safer, communicates intent.

```php
final class ActivateOrderAction { ... }
final class EloquentOrderRepository implements OrderRepositoryInterface { ... }
final readonly class OrderData { ... }
```

---

### 6.3 [WARNING] Readonly Properties and DTOs

**Rule:** DTOs and value objects MUST use `readonly` properties or `readonly` class modifier.

```php
final readonly class ActivateOrderInput
{
    public function __construct(
        public string $orderId,
    ) {}
}
```

---

### 6.4 [SUGGESTION] Early Returns Over Nesting

**Rule:** Prefer early returns and guard clauses over deeply nested conditionals.

**Bad:**
```php
public function execute(ActivateOrderInput $input): OrderData
{
    if (!empty($input->orderId)) {
        $order = $this->orderRepo->find($input->orderId);
        if ($order !== null) {
            if ($order->status() === Status::Pending) {
                $order->activate($this->clock->now());
                $this->orderRepo->save($order);
                return OrderData::fromDomain($order);
            }
        }
    }
    throw new \InvalidArgumentException('Invalid');
}
```

**Good:**
```php
public function execute(ActivateOrderInput $input): OrderData
{
    $order = $this->orderRepo->findOrFail($input->orderId);

    $order->activate($this->clock->now());

    $this->orderRepo->save($order);

    return OrderData::fromDomain($order);
}
```

---

### 6.5 [WARNING] Run Laravel Pint

**Rule:** All code MUST pass `./vendor/bin/pint` with the team's configuration.

---

### 6.6 [CRITICAL] Use Enums for Finite State

**Rule:** Use PHP 8.1+ backed enums for status fields, types, and any finite set of values. NEVER use string constants or magic strings.

**Bad:**
```php
$order->status = 'active';         // Magic string
const STATUS_ACTIVE = 'active';    // String constant
```

**Good:**
```php
enum Status: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
```

---

## 7. Naming Conventions

### 7.1 [WARNING] Class Names

**Rule:**
- Actions: `VerbNounAction` (e.g., `ActivateOrderAction`, `CreatePaymentAction`)
- Repositories: `EloquentXxxRepository` implements `XxxRepositoryInterface`
- Domain models: singular nouns (`Order`, `Payment`, `Customer`)
- DTOs: `XxxData` or `XxxInput` (e.g., `OrderData`, `ActivateOrderInput`)
- Events: past tense (`OrderActivated`, `PaymentProcessed`)

---

### 7.2 [WARNING] Method Names

**Rule:**
- Domain behavior: intention-revealing verbs (`activate()`, `cancel()`, `applyDiscount()`)
- Repository reads: `find()`, `findOrFail()`, `findByStatus()`, `list()`
- Repository writes: `save()`, `delete()`
- Action entry point: `execute()`

---

### 7.3 [WARNING] Constructor Naming

**Rule:**
- Create new entities: `static create(...)` or `new Entity(...)`
- Reconstitute from DB: `static reconstitute(...)` - no validation, no events

```php
// Creating new aggregate (validates, raises events)
$order = Order::create($id, $customerId, $lines, $now);

// Reconstituting from DB (no validation, no events)
$order = Order::reconstitute(
    id: new OrderId($model->id),
    status: Status::from($model->status),
    // ...
);
```

---

### 7.4 [CRITICAL] Money Variable Naming

**Rule:** ALL currency variable/field/parameter names MUST be suffixed with `Amount`.

**Bad:**
```php
class Order
{
    private Money $total; // Missing "Amount" suffix
    private Money $tax;
}
```

**Good:**
```php
class Order
{
    private Money $totalAmount;
    private Money $taxAmount;
}
```

---

## 8. Money & Currency Rules

### 8.1 [CRITICAL] NEVER Use Floats for Money

**Rule:** ALL currency amounts MUST use a Money value object backed by integer cents or `bcmath`. NEVER use `float` or `double`.

**Why:** Floating-point arithmetic causes precision errors in financial calculations. `0.1 + 0.2 !== 0.3` in IEEE 754.

**Bad:**
```php
class Order
{
    public float $total; // NEVER use float for money

    public function calculateTotal(array $items): float
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $sum += $item->price * $item->quantity; // Precision loss
        }
        return $sum;
    }
}
```

**Good:**
```php
use App\Domain\Shared\Money;

class Order
{
    private Money $totalAmount;

    public function calculateTotal(array $lines): Money
    {
        $sum = Money::zero();
        foreach ($lines as $line) {
            $sum = $sum->add(
                $line->priceAmount()->multiply($line->quantity())
            );
        }
        return $sum;
    }
}
```

**Money Value Object:**
```php
namespace App\Domain\Shared;

final readonly class Money
{
    private function __construct(
        private int $cents,
        private string $currency = 'USD',
    ) {}

    public static function fromCents(int $cents, string $currency = 'USD'): self
    {
        return new self($cents, $currency);
    }

    public static function fromDecimal(string $decimal, string $currency = 'USD'): self
    {
        return new self(
            (int) bcmul($decimal, '100', 0),
            $currency,
        );
    }

    public static function zero(string $currency = 'USD'): self
    {
        return new self(0, $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->cents + $other->cents, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->cents - $other->cents, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->cents * $factor, $this->currency);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function formatted(): string
    {
        return bcdiv((string) $this->cents, '100', 2);
    }

    public function greaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->cents > $other->cents;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException($this->currency, $other->currency);
        }
    }
}
```

---

### 8.2 [CRITICAL] No Floats in Tests Either

**Rule:** Tests MUST also use the Money value object. No exceptions.

**Bad:**
```php
public function test_calculate_total(): void
{
    $total = $order->calculateTotal($items);
    $this->assertEquals(99.99, $total); // Float in test
}
```

**Good:**
```php
public function test_calculate_total(): void
{
    $total = $order->calculateTotal($items);
    $expected = Money::fromCents(9999);
    $this->assertTrue($total->equals($expected));
}
```

---

### 8.3 [CRITICAL] Store Money as Integer Cents in Database

**Rule:** Database columns for currency MUST be `BIGINT` storing cents, NEVER `DECIMAL` or `FLOAT`.

```php
// Migration
Schema::create('orders', function (Blueprint $table) {
    $table->bigInteger('total_amount_cents');   // Cents
    $table->string('total_amount_currency', 3); // ISO 4217
});
```

---

## 9. Testing Requirements

### 9.1 [WARNING] Tests for Changed Code

**Rule:** PRs that modify code MUST include or update tests.

**Check for:**
- New public methods without tests
- Modified business logic without test updates
- Deleted tests without justification

---

### 9.2 [SUGGESTION] Data Provider Tests

**Rule:** Prefer data providers for multiple scenarios.

```php
#[DataProvider('activationStatusProvider')]
public function test_activate_from_status(Status $initialStatus, bool $shouldSucceed): void
{
    $order = OrderFactory::withStatus($initialStatus);

    if (!$shouldSucceed) {
        $this->expectException(InvalidTransitionException::class);
    }

    $order->activate(new DateTimeImmutable());

    if ($shouldSucceed) {
        $this->assertSame(Status::Active, $order->status());
    }
}

public static function activationStatusProvider(): array
{
    return [
        'success from confirmed' => [Status::Confirmed, true],
        'fails from draft'       => [Status::Draft, false],
        'fails from cancelled'   => [Status::Cancelled, false],
        'fails from active'      => [Status::Active, false],
    ];
}
```

---

### 9.3 [WARNING] Test Domain Logic Independently

**Rule:** Domain tests MUST NOT require database, HTTP, or external dependencies.

```php
public function test_order_calculate_total(): void
{
    // Pure domain test - no DB, no mocks needed
    $order = Order::create(
        id: OrderId::generate(),
        customerId: new CustomerId('cust-1'),
        lines: [
            OrderLine::create('prod-1', 2, Money::fromCents(9999)),
        ],
        now: new DateTimeImmutable(),
    );

    $total = $order->calculateTotal();
    $this->assertTrue($total->equals(Money::fromCents(19998)));
}
```

---

### 9.4 [CRITICAL] Test Architecture & Test Pyramid

**Rule:** Follow the test pyramid: 70% unit, 20% feature/integration, 10% E2E.

```
        /\              10% E2E / Browser Tests
       /  \             - Test complete user journeys
      /    \            - Slow, fragile
     /------\
    /        \          20% Feature Tests
   /          \         - Test actions with real DB
  /            \        - HTTP tests for API endpoints
 /--------------\
/                \      70% Unit Tests
/  Unit Tests     \     - Domain logic (pure PHP)
/                  \    - Value objects, entities
--------------------    - No DB, no HTTP
```

**Test Types:**

| Test Type | What to Test | Uses DB | Uses HTTP |
|-----------|-------------|---------|-----------|
| Unit | Domain aggregates, value objects, domain services | No | No |
| Feature | Actions, repositories, API endpoints | Yes | Optional |
| E2E | Full user workflows | Yes | Yes |

---

### 9.5 [CRITICAL] Test Coverage Requirements

**Rule:** Minimum coverage by layer:

| Layer | Minimum Coverage | What to Test |
|-------|-----------------|--------------|
| Domain | 90%+ | All business rules, state transitions, value objects |
| Actions | 80%+ | Happy path, validation errors, business rule violations |
| Repository | 70%+ | CRUD operations, mapping correctness |
| Controllers | 60%+ | Request validation, response format, auth |

---

### 9.6 [CRITICAL] Field-by-Field Update Testing

**Rule:** When testing update operations, test EVERY field individually plus combinations.

```php
#[DataProvider('fieldUpdateProvider')]
public function test_update_individual_fields(string $field, mixed $value, mixed $expected): void
{
    $config = ConfigFactory::createDefault();

    $this->action->execute(new UpdateConfigInput(
        configId: $config->id(),
        updates: [$field => $value],
    ));

    $updated = $this->configRepo->findOrFail($config->id());
    $this->assertSame($expected, $this->getFieldValue($updated, $field));
}

public static function fieldUpdateProvider(): array
{
    return [
        'update facility_id'      => ['facility_id', 'new-facility-123', 'new-facility-123'],
        'update status to active' => ['status', 'active', 'active'],
        'update alert window'     => ['alert_window_minutes', 60, 60],
        'set alert window to 0'   => ['alert_window_minutes', 0, 0],
        'clear description'       => ['description', null, null],
    ];
}
```

---

### 9.7 [CRITICAL] Mock Patterns

**Rule:** Mock repository interfaces and external services. DON'T mock domain aggregates.

```
DO Mock:
- Repository interfaces (for action unit tests)
- External API clients (payment gateways, etc.)
- Clock interface (deterministic time)
- UUID generator (deterministic IDs)

DON'T Mock:
- Domain aggregates (test them directly)
- Value objects (test them directly)
- Enums
```

**Mock Repository in Action Test:**

```php
public function test_activate_order_success(): void
{
    $order = OrderFactory::createConfirmed();
    $now = new DateTimeImmutable('2026-01-15 10:30:00');

    $this->orderRepo
        ->shouldReceive('findOrFail')
        ->with('order-123')
        ->andReturn($order);

    $this->orderRepo
        ->shouldReceive('save')
        ->once()
        ->with(Mockery::on(fn (Order $o) => $o->status() === Status::Active));

    $this->clock
        ->shouldReceive('now')
        ->andReturn($now);

    $result = $this->action->execute(new ActivateOrderInput(orderId: 'order-123'));

    $this->assertSame('active', $result->status);
}
```

---

### 9.8 [WARNING] Use Clock Abstraction for Deterministic Time

**Rule:** NEVER use `now()`, `Carbon::now()`, or `new \DateTimeImmutable()` directly inside domain or actions. Use an injected Clock interface.

```php
interface Clock
{
    public function now(): DateTimeImmutable;
}

final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}

final class FixedClock implements Clock
{
    public function __construct(
        private readonly DateTimeImmutable $fixedTime,
    ) {}

    public function now(): DateTimeImmutable
    {
        return $this->fixedTime;
    }
}
```

---

### 9.9 [WARNING] Test Performance Targets

**Rule:** Tests must execute quickly.

| Test Type | Target | Max Acceptable |
|-----------|--------|----------------|
| Unit test | < 50ms | < 200ms |
| Feature test | < 500ms | < 2s |
| E2E test | < 3s | < 10s |
| Full suite | < 60s | < 120s |

---

## 10. Documentation Standards

### 10.1 [WARNING] Class-Level DocBlocks

**Rule:** Actions, domain aggregates, and repository interfaces MUST have class-level docblocks.

```php
/**
 * Activates an order after payment confirmation.
 *
 * Triggers inventory reservation and shipment preparation.
 * Order must be in Confirmed status.
 *
 * Flow:
 *  1. Load order aggregate via repository
 *  2. Call domain activate method (validates transition)
 *  3. Persist changes via repository
 *  4. Dispatch domain events
 */
final class ActivateOrderAction
{
```

---

### 10.2 [SUGGESTION] Document State Transitions

**Rule:** Aggregate classes SHOULD document their state machine.

```php
/**
 * Order aggregate.
 *
 * State Transitions:
 *   Draft -> Confirmed -> Active -> Shipped -> Completed
 *                    \-> Cancelled
 *
 * Invariants:
 *   - Must have at least one order line
 *   - Total amount equals sum of line items
 *   - Cannot modify in terminal state (Completed, Cancelled)
 *   - Customer ID is immutable after creation
 */
class Order
{
```

---

## 11. Security & Safety Rules

### 11.1 [CRITICAL] Always Use Form Requests

**Rule:** Every controller method that accepts user input MUST use a Form Request class for validation. NEVER validate inline.

**Bad:**
```php
public function store(Request $request): JsonResponse
{
    $request->validate([          // Inline validation
        'email' => 'required|email',
    ]);
}
```

**Good:**
```php
public function store(CreateUserRequest $request): JsonResponse
{
    // $request is already validated
}
```

---

### 11.2 [CRITICAL] No Mass Assignment Vulnerabilities

**Rule:** Eloquent models used in repositories MUST have `$fillable` or `$guarded` defined. Never use `Model::create($request->all())`.

**Bad:**
```php
OrderModel::create($request->all());  // Mass assignment vulnerability
```

**Good:**
```php
OrderModel::create([
    'customer_id' => $input->customerId,
    'status' => Status::Draft->value,
    'total_amount_cents' => $input->totalAmountCents,
]);
```

---

### 11.3 [CRITICAL] No Secrets in Code

**Rule:** NEVER commit secrets, API keys, or credentials. Use `.env` and `config()`.

**Check for:**
- Hardcoded API keys, passwords, or tokens
- `.env` files committed to version control
- Credentials in config files

---

### 11.4 [CRITICAL] Authorization in Every Endpoint

**Rule:** Every controller method MUST check authorization via policies or gates. No endpoint should be accessible without authorization checks.

```php
public function activate(ActivateOrderRequest $request, string $id): JsonResponse
{
    $order = Order::findOrFail($id);
    $this->authorize('activate', $order); // Policy check

    $result = $this->activateOrder->execute(
        new ActivateOrderInput(orderId: $id)
    );

    return response()->json(OrderResource::make($result));
}
```

---

### 11.5 [WARNING] SQL Injection Prevention

**Rule:** NEVER use raw string interpolation in queries. Always use parameter binding.

**Bad:**
```php
DB::select("SELECT * FROM orders WHERE id = '{$id}'"); // SQL injection
```

**Good:**
```php
DB::select('SELECT * FROM orders WHERE id = ?', [$id]);
OrderModel::where('id', $id)->first();
```

---

## 12. General Best Practices

### 12.1 [WARNING] Small PRs

**Rule:** Keep PRs small (< 10 files) and focused on one logical change.

---

### 12.2 [WARNING] No Dead Code

**Rule:** Remove commented-out code, unused imports, unused methods, and dead routes.

---

### 12.3 [CRITICAL] No Circular Dependencies

**Rule:** Packages/namespaces MUST NOT create circular dependencies.

**Check for:**
- Domain importing from application or infrastructure
- Actions importing from controllers
- Two actions importing each other

---

### 12.4 [WARNING] Quality Gates

**Rule:** All PRs MUST pass before merge:
- `./vendor/bin/pint` - Code style
- `./vendor/bin/phpstan analyse` - Static analysis (level 8+ preferred)
- `php artisan test` - All tests pass
- `php artisan route:list` - Routes compile

---

## 13. Generated Code & Artifacts

### 13.1 [WARNING] Never Commit Generated Files

**Rule:** Do not commit generated or cached files.

**Check for:**
- `storage/framework/cache/*` committed
- `bootstrap/cache/*` committed
- IDE configuration files (`.idea/`, `.vscode/`)
- `vendor/` directory committed

---

### 13.2 [WARNING] API Resources Over Raw Arrays

**Rule:** Always use API Resources for JSON responses. Never return raw arrays or Eloquent models.

**Bad:**
```php
return response()->json($order->toArray()); // Raw model
return response()->json(['id' => $order->id, 'status' => $order->status]); // Raw array
```

**Good:**
```php
return response()->json(OrderResource::make($orderData));
```

---

## 14. Pull Request Standards

### 14.1 [CRITICAL] PR Description Required

**Rule:** ALL pull requests MUST include a comprehensive description.

```markdown
## Summary
[1-3 sentences explaining WHAT changed and WHY]

## Changes
- [Bullet point list of key modifications]

## Testing
- [How you tested the changes]
- [Unit tests added/updated]
- [Manual testing performed]

## Breaking Changes
- [Any backwards incompatibilities]
- [Migration steps required]
- [Or "None"]

## Related Ticket
[JIRA-123] or [GitHub Issue #456]
```

---

### 14.2 [WARNING] PR Size Limits

| Files Changed | Status | Action |
|--------------|--------|--------|
| 1-10 files | Ideal | Good size |
| 11-20 files | Large | Acceptable for cohesive features |
| 21-30 files | Too Large | Consider splitting |
| 30+ files | Way Too Large | Must split or justify |

---

### 14.3 [SUGGESTION] PR Title Format

```
<type>(<scope>): <description>

Examples:
feat(order): add activation workflow
fix(payment): resolve currency rounding edge case
refactor(domain): extract order validation to value object
```

---

## 15. Dependency Management

### 15.1 [WARNING] Semantic Versioning Awareness

**Rule:** Understand semantic versioning when reviewing `composer.json` changes.

| Change Type | Example | Risk |
|------------|---------|------|
| MAJOR | v1.x -> v2.x | High - Breaking changes |
| MINOR | v1.2.x -> v1.3.x | Low - New features |
| PATCH | v1.2.3 -> v1.2.4 | Minimal - Bug fixes |

---

### 15.2 [CRITICAL] composer.lock Integrity

**Rule:** `composer.lock` MUST always be committed and kept in sync with `composer.json`.

```bash
# Verify integrity after dependency changes
composer validate
composer install --dry-run
```

---

### 15.3 [WARNING] No Unnecessary Dependencies

**Rule:** Don't add packages for things PHP or Laravel already handles.

**Check for:**
- Array utility packages when `Collection` or `array_*` functions suffice
- String utility packages when `Str::` helpers suffice
- HTTP client packages when Laravel's HTTP facade works

---

## 16. AI Review Tool Implementation Guide

### 16.1 [CRITICAL] Architecture Flow Compliance

**Purpose:** Enforce the mandatory data flow.

**Detection Pattern:**

```python
def check_architecture_flow(pr_files, diff):
    issues = []

    for file_path, file_diff in diff.items():
        if '_test.php' in file_path or 'Test.php' in file_path:
            continue

        # Detect layer from file path
        current_layer = 'unknown'
        if '/Controllers/' in file_path or '/Http/' in file_path:
            current_layer = 'controller'
        elif '/Actions/' in file_path:
            current_layer = 'action'
        elif '/Domain/' in file_path:
            current_layer = 'domain'
        elif '/Repositories/' in file_path:
            current_layer = 'repository'

        # CRITICAL: Controller directly using Eloquent
        if current_layer == 'controller':
            eloquent_patterns = [
                r'::find\(', r'::findOrFail\(',
                r'::where\(', r'::create\(',
                r'->save\(\)', r'->update\(',
                r'->delete\(\)',
                r'DB::table\(', r'DB::select\(',
            ]
            for pattern in eloquent_patterns:
                if re.search(pattern, file_diff):
                    issues.append({
                        'severity': 'CRITICAL',
                        'file': file_path,
                        'message': 'Controller directly accessing database',
                        'fix': 'Move to Action + Repository pattern',
                    })
                    break

        # CRITICAL: Domain using facades or Eloquent
        if current_layer == 'domain':
            facade_patterns = [
                r'use Illuminate\\Support\\Facades\\',
                r'DB::', r'Cache::', r'Log::', r'Event::',
                r'->save\(\)', r'->delete\(\)', r'->refresh\(\)',
                r'extends Model',
            ]
            for pattern in facade_patterns:
                if re.search(pattern, file_diff):
                    issues.append({
                        'severity': 'CRITICAL',
                        'file': file_path,
                        'message': 'Domain layer has infrastructure dependency',
                        'fix': 'Domain must be pure PHP with no framework imports',
                    })
                    break

        # CRITICAL: Action importing concrete repository
        if current_layer == 'action':
            if re.search(r'use App\\Repositories\\Eloquent\\', file_diff):
                issues.append({
                    'severity': 'CRITICAL',
                    'file': file_path,
                    'message': 'Action importing concrete repository',
                    'fix': 'Import interface from Contracts namespace',
                })

    return issues
```

---

### 16.2 [CRITICAL] Money Type Validation

**Detection Pattern:**

```python
def check_money_types(pr_files, diff):
    issues = []

    for file_path, file_diff in diff.items():
        # CRITICAL: Float for currency
        float_money_patterns = [
            r'float\s+\$\w*(price|cost|total|amount|fee|tax|discount|balance|rate)',
            r'(float|double)\s+\$\w*Amount',
            r'\$\w*(Amount|Price|Cost|Total)\s*=\s*[\d.]+[^;]*;',  # numeric literal
        ]
        for pattern in float_money_patterns:
            if re.search(pattern, file_diff, re.IGNORECASE):
                issues.append({
                    'severity': 'CRITICAL',
                    'file': file_path,
                    'message': 'Float used for money - use Money value object',
                    'fix': 'Replace float with Money::fromCents() or Money::fromDecimal()',
                })

        # WARNING: Missing Amount suffix
        money_without_suffix = [
            r'private\s+Money\s+\$(total|price|cost|fee|tax|discount|balance)',
        ]
        for pattern in money_without_suffix:
            if re.search(pattern, file_diff):
                issues.append({
                    'severity': 'WARNING',
                    'file': file_path,
                    'message': 'Money field missing "Amount" suffix',
                    'fix': 'Rename: $total -> $totalAmount',
                })

    return issues
```

---

### 16.3 [WARNING] Strict Types Detection

```python
def check_strict_types(pr_files, diff):
    issues = []

    for file_path, file_diff in diff.items():
        if not file_path.endswith('.php'):
            continue

        if 'declare(strict_types=1)' not in file_diff:
            if 'namespace App\\' in file_diff:
                issues.append({
                    'severity': 'WARNING',
                    'file': file_path,
                    'message': 'Missing declare(strict_types=1)',
                    'fix': 'Add declare(strict_types=1) after <?php',
                })

    return issues
```

---

### 16.4 [CRITICAL] N+1 Query Detection

```python
def check_n_plus_one(pr_files, diff):
    issues = []

    for file_path, file_diff in diff.items():
        # Detect queries inside loops
        if re.search(r'(foreach|for|while)\s*\([^)]*\)\s*\{[^}]*(::(find|where|first)|->load\()', file_diff, re.DOTALL):
            issues.append({
                'severity': 'CRITICAL',
                'file': file_path,
                'message': 'Potential N+1 query inside loop',
                'fix': 'Use eager loading with ->with() or batch query outside loop',
            })

    return issues
```

---

## 17. Database Transaction Pattern

### 17.1 [CRITICAL] All Writes in Transactions

**Rule:** ALL write operations MUST be wrapped in `DB::transaction()`.

**Why:** Ensures atomicity - either all changes succeed or none do.

**Bad:**
```php
public function execute(TransferInput $input): void
{
    $fromAccount = $this->accountRepo->findOrFail($input->fromAccountId);
    $toAccount = $this->accountRepo->findOrFail($input->toAccountId);

    $fromAccount->debit($input->amount);
    $this->accountRepo->save($fromAccount);

    // If this fails, fromAccount is already debited!
    $toAccount->credit($input->amount);
    $this->accountRepo->save($toAccount);
}
```

**Good:**
```php
public function execute(TransferInput $input): void
{
    DB::transaction(function () use ($input) {
        $fromAccount = $this->accountRepo->findOrFail($input->fromAccountId);
        $toAccount = $this->accountRepo->findOrFail($input->toAccountId);

        $fromAccount->debit($input->amount);
        $toAccount->credit($input->amount);

        $this->accountRepo->save($fromAccount);
        $this->accountRepo->save($toAccount);
    });
}
```

---

### 17.2 [WARNING] Dispatch Events After Transaction Commits

**Rule:** Domain events that trigger external side effects (emails, queued jobs, webhooks) MUST be dispatched AFTER the transaction commits, not inside it.

**Why:** If the transaction rolls back, events dispatched inside it will have fired for data that doesn't exist.

**Bad:**
```php
DB::transaction(function () use ($input) {
    $order = $this->orderRepo->findOrFail($input->orderId);
    $order->activate($this->clock->now());
    $this->orderRepo->save($order);

    // Dispatched INSIDE transaction - if transaction fails, event already fired
    event(new OrderActivated($order->id()));
});
```

**Good:**
```php
$order = DB::transaction(function () use ($input) {
    $order = $this->orderRepo->findOrFail($input->orderId);
    $order->activate($this->clock->now());
    $this->orderRepo->save($order);
    return $order;
});

// Dispatched AFTER successful commit
foreach ($order->pullEvents() as $event) {
    $this->dispatcher->dispatch($event);
}
```

Or use Laravel's `afterCommit`:

```php
DB::transaction(function () use ($input) {
    $order = $this->orderRepo->findOrFail($input->orderId);
    $order->activate($this->clock->now());
    $this->orderRepo->save($order);

    DB::afterCommit(function () use ($order) {
        foreach ($order->pullEvents() as $event) {
            event($event);
        }
    });
});
```

---

### 17.3 [WARNING] Avoid Long-Running Transactions

**Rule:** Transactions SHOULD complete in < 1 second. Avoid HTTP calls, queue dispatches, or slow operations inside transactions.

**Bad:**
```php
DB::transaction(function () {
    $order = $this->orderRepo->findOrFail($id);
    $order->activate($now);
    $this->orderRepo->save($order);

    // HTTP call inside transaction holds the lock!
    $this->paymentGateway->charge($order->totalAmount());

    // Queue dispatch inside transaction
    SendConfirmationEmail::dispatch($order);
});
```

**Good:**
```php
// Transaction for DB changes only
$order = DB::transaction(function () {
    $order = $this->orderRepo->findOrFail($id);
    $order->activate($now);
    $this->orderRepo->save($order);
    return $order;
});

// External calls AFTER transaction
$this->paymentGateway->charge($order->totalAmount());
SendConfirmationEmail::dispatch($order);
```

---

### 17.4 [CRITICAL] Controllers NEVER Manage Transactions

**Rule:** `DB::transaction()` belongs in actions, NEVER in controllers.

**Bad:**
```php
class OrderController extends Controller
{
    public function activate(Request $request, string $id): JsonResponse
    {
        DB::transaction(function () use ($id) {
            // Controller managing transaction
        });
    }
}
```

**Good:**
```php
// Action manages transaction internally
final class ActivateOrderAction
{
    public function execute(ActivateOrderInput $input): OrderData
    {
        return DB::transaction(function () use ($input) {
            // ...
        });
    }
}
```

---

## 18. Domain Events & Queue Pattern

### 18.1 [CRITICAL] Domain Events for State Changes

**Rule:** Significant state changes MUST raise domain events. Events are raised in domain, dispatched in actions.

```php
// Domain raises event
class Order
{
    use RaisesEvents;

    public function activate(DateTimeImmutable $now): void
    {
        $this->status = Status::Active;
        $this->activatedAt = $now;
        $this->recordChange('status', $this->status->value);

        $this->raise(new OrderActivated(
            orderId: $this->id->toString(),
            customerId: $this->customerId->toString(),
            activatedAt: $now,
        ));
    }
}

// Action dispatches events
final class ActivateOrderAction
{
    public function execute(ActivateOrderInput $input): OrderData
    {
        $order = DB::transaction(function () use ($input) {
            $order = $this->orderRepo->findOrFail($input->orderId);
            $order->activate($this->clock->now());
            $this->orderRepo->save($order);
            return $order;
        });

        // Dispatch after commit
        foreach ($order->pullEvents() as $event) {
            event($event);
        }

        return OrderData::fromDomain($order);
    }
}
```

---

### 18.2 [WARNING] Queued Listeners for Heavy Work

**Rule:** Event listeners that do heavy work (sending emails, calling APIs, generating PDFs) MUST implement `ShouldQueue`.

```php
final class SendOrderConfirmationListener implements ShouldQueue
{
    public function handle(OrderActivated $event): void
    {
        // This runs in the queue, not during the HTTP request
        Mail::to($event->customerEmail)->send(
            new OrderConfirmationMail($event->orderId)
        );
    }
}
```

---

### 18.3 [WARNING] Event Naming Convention

**Rule:** Domain events MUST be named in past tense and describe what happened.

```
OrderActivated
PaymentProcessed
ShipmentDispatched
CustomerRegistered
InvoiceGenerated
```

---

## 19. Anti-Patterns Consolidated (NEVER DO)

### 19.1 [CRITICAL] Controller Anti-Patterns

**NEVER:**
- Put business logic in controllers
- Call Eloquent models directly from controllers
- Manage `DB::transaction()` in controllers
- Return Eloquent models from controllers
- Perform authorization checks inside actions (belongs in controller/middleware)

---

### 19.2 [CRITICAL] Action Anti-Patterns

**NEVER:**
- Import concrete repository implementations (use interfaces)
- Import other actions (use shared services/interfaces)
- Use facades (inject dependencies instead)
- Return domain aggregates to controllers (return DTOs)
- Accept `Request` objects (accept typed input DTOs)

---

### 19.3 [CRITICAL] Domain Anti-Patterns

**NEVER:**
- Use facades (`DB::`, `Cache::`, `Log::`, etc.)
- Extend Eloquent `Model`
- Call `$this->save()` or any persistence method
- Accept `Request`, `Carbon`, or other framework objects as parameters
- Import from `Illuminate\` namespace
- Have any infrastructure dependency

---

### 19.4 [CRITICAL] Repository Anti-Patterns

**NEVER:**
- Put business logic in repositories
- Return Eloquent models from repository interfaces (return domain objects)
- Have multiple repositories for the same aggregate
- Call other repositories (composition belongs in actions)

---

### 19.5 [CRITICAL] Money Anti-Patterns

**NEVER:**
- Use `float` or `double` for currency
- Store money as `DECIMAL` in database (use `BIGINT` cents)
- Compare money with `==` (use `->equals()`)
- Do arithmetic with `+` `-` `*` `/` on money values (use Money methods)

---

### 19.6 [CRITICAL] General Anti-Patterns

**NEVER:**
- Use `app()` for service location inside domain/action (use constructor injection)
- Use `env()` outside of config files
- Use magic strings for statuses (use enums)
- Skip `declare(strict_types=1)`
- Leave unused `use` imports
- Commit `.env`, `vendor/`, or `storage/` to version control

---

## 20. Observability & Logging

### 20.1 [WARNING] Structured Logging

**Rule:** Use structured logging with context arrays, not string interpolation.

**Bad:**
```php
Log::info("Order {$orderId} activated by user {$userId}");
```

**Good:**
```php
Log::info('Order activated', [
    'order_id' => $orderId,
    'user_id' => $userId,
    'new_status' => $status->value,
]);
```

---

### 20.2 [WARNING] No Logging in Domain Layer

**Rule:** Domain MUST NOT log. Logging is an infrastructure concern handled in actions or middleware.

---

### 20.3 [SUGGESTION] Request ID Propagation

**Rule:** Every request SHOULD have a unique request ID propagated through logs.

```php
// Middleware
final class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id', (string) Str::uuid());
        Log::withContext(['request_id' => $requestId]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
```

---

### 20.4 [WARNING] Telescope for Local Development

**Rule:** Laravel Telescope MUST be installed for local development. It MUST NOT be enabled in production.

---

## 21. Performance Optimization

### 21.1 [CRITICAL] Eager Loading Required

**Rule:** All Eloquent queries that access relationships MUST use eager loading.

**Bad:**
```php
$orders = OrderModel::all();
foreach ($orders as $order) {
    $customer = $order->customer; // N+1 query
    $items = $order->items;       // N+1 query
}
```

**Good:**
```php
$orders = OrderModel::with(['customer', 'items'])->get();
```

---

### 21.2 [WARNING] Chunked Processing for Large Datasets

**Rule:** Processing large datasets MUST use chunking or cursor.

**Bad:**
```php
$users = User::all(); // Loads millions into memory
foreach ($users as $user) {
    // process
}
```

**Good:**
```php
User::query()->chunk(1000, function ($users) {
    foreach ($users as $user) {
        // process
    }
});

// Or with lazy collection
User::query()->lazy()->each(function ($user) {
    // process
});
```

---

### 21.3 [WARNING] Select Only Needed Columns

**Rule:** Queries SHOULD select only the columns they need, especially for list endpoints.

```php
OrderModel::select(['id', 'status', 'total_amount_cents', 'created_at'])
    ->where('company_id', $companyId)
    ->paginate(25);
```

---

### 21.4 [WARNING] Cache Appropriately

**Rule:** Expensive queries and computations SHOULD be cached with explicit TTLs and invalidation.

```php
$dashboardStats = Cache::remember(
    "dashboard:stats:{$companyId}",
    now()->addMinutes(5),
    fn () => $this->statsQuery->execute($companyId),
);
```

---

### 21.5 [CRITICAL] Database Indexing

**Rule:** Every `WHERE`, `ORDER BY`, and `JOIN` column MUST have an appropriate index.

```php
Schema::create('orders', function (Blueprint $table) {
    // ...
    $table->index('company_id');
    $table->index('status');
    $table->index(['company_id', 'status']); // Composite for filtered queries
    $table->index('created_at');
});
```

---

## 22. CQRS Pattern Details

### 22.1 [CRITICAL] Command vs Query Separation

**Rule:** Separate write operations (actions) from read operations (queries).

**Structure:**
```
app/
  Actions/           # WRITE operations (commands)
    Order/
      ActivateOrderAction.php
      CreateOrderAction.php
  Queries/            # READ operations
    Order/
      GetOrderQuery.php
      ListOrdersQuery.php
```

---

### 22.2 [CRITICAL] Queries Return DTOs, Not Domain Objects

**Rule:** Query classes return lightweight DTOs or arrays, NEVER domain aggregates.

```php
final class GetOrderQuery
{
    public function execute(string $orderId): OrderData
    {
        $model = OrderModel::select(['id', 'status', 'total_amount_cents', 'created_at'])
            ->findOrFail($orderId);

        return new OrderData(
            id: $model->id,
            status: $model->status,
            totalAmount: bcdiv((string) $model->total_amount_cents, '100', 2),
            createdAt: $model->created_at->toIso8601String(),
        );
    }
}
```

---

### 22.3 [CRITICAL] Actions Return Error or Minimal Data

**Rule:** Write actions return `void` or a minimal DTO (e.g., just the created ID). Never query back the full entity just to return it.

**Good:**
```php
final class CreateOrderAction
{
    public function execute(CreateOrderInput $input): string // Returns ID only
    {
        return DB::transaction(function () use ($input) {
            $order = Order::create(/* ... */);
            $this->orderRepo->save($order);
            return $order->id()->toString();
        });
    }
}

// Controller queries separately for the response
public function store(CreateOrderRequest $request): JsonResponse
{
    $orderId = $this->createOrder->execute(/* ... */);
    $orderData = $this->getOrder->execute($orderId);
    return response()->json(OrderResource::make($orderData), 201);
}
```

---

### 22.4 [WARNING] Queries Can Bypass Repository

**Rule:** Read-only queries MAY use Eloquent directly (bypassing the repository) for performance. Queries don't modify state, so the full domain aggregate isn't needed.

```php
// Acceptable: Query uses Eloquent directly for read performance
final class ListOrdersQuery
{
    public function execute(ListOrdersInput $input): LengthAwarePaginator
    {
        return OrderModel::query()
            ->select(['id', 'status', 'total_amount_cents', 'created_at'])
            ->where('company_id', $input->companyId)
            ->when($input->status, fn ($q) => $q->where('status', $input->status))
            ->orderByDesc('created_at')
            ->paginate($input->perPage);
    }
}
```

---

## 23. Deployment & Release

### 23.1 [CRITICAL] Migrations Must Be Backward Compatible

**Rule:** Database migrations MUST NOT break the currently running version of the application.

**Bad:**
```php
// Renaming a column that existing code depends on
Schema::table('orders', function (Blueprint $table) {
    $table->renameColumn('total', 'total_amount_cents'); // Breaks running code
});
```

**Good (two-step deploy):**
```php
// Step 1 (deploy first): Add new column, backfill
Schema::table('orders', function (Blueprint $table) {
    $table->bigInteger('total_amount_cents')->nullable();
});
// Backfill existing data
DB::statement('UPDATE orders SET total_amount_cents = total * 100');

// Step 2 (deploy after code uses new column): Drop old column
Schema::table('orders', function (Blueprint $table) {
    $table->dropColumn('total');
});
```

---

### 23.2 [CRITICAL] Health Checks

**Rule:** Every service MUST expose a health check endpoint.

```php
Route::get('/health', function () {
    try {
        DB::select('SELECT 1');
        Cache::get('health-check');

        return response()->json(['status' => 'healthy'], 200);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'unhealthy'], 503);
    }
});
```

---

### 23.3 [WARNING] Zero-Downtime Deploys

**Rule:** Deployments MUST support zero-downtime. Use `php artisan down --retry=60` only as last resort.

**Deployment checklist:**
- Run `composer install --no-dev` before swapping
- Run `php artisan migrate` with backward-compatible migrations
- Run `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`
- Restart queue workers: `php artisan queue:restart`

---

### 23.4 [WARNING] Semantic Versioning and Changelog

**Rule:** Use semantic versioning and maintain a CHANGELOG.md.

---

## PR Review Checklist Summary

Use this quick checklist for every PR review:

- [ ] Architecture flow respected (Section 1)
- [ ] Domain purity maintained (Section 2)
- [ ] Repository pattern followed (Section 3)
- [ ] Actions properly structured (Section 4)
- [ ] Error handling correct (Section 5)
- [ ] Code style clean (Section 6)
- [ ] Money handling safe (Section 8)
- [ ] Tests adequate (Section 9)
- [ ] Security checks pass (Section 11)
- [ ] DB transactions correct (Section 17)
- [ ] Events dispatched after commit (Section 18)
- [ ] No anti-patterns (Section 19)
- [ ] No N+1 queries (Section 21)
- [ ] CQRS separation maintained (Section 22)

---

## Example Review Comments

### CRITICAL Issue Template

```
[CRITICAL] Controller directly accessing Eloquent model

File: app/Http/Controllers/OrderController.php:42

Found: Order::findOrFail($id)->update(['status' => 'active'])

This bypasses the entire Action -> Domain -> Repository flow.

Fix: Create ActivateOrderAction that loads the domain aggregate,
calls activate() domain method, and persists via repository.

Reference: PR_REVIEW_RULES.md Section 1.1
```

### WARNING Issue Template

```
[WARNING] Missing eager loading - potential N+1 query

File: app/Queries/Order/ListOrdersQuery.php:28

Found: OrderModel::all() followed by ->customer access in loop.

Fix: Add ->with(['customer']) to the query.

Reference: PR_REVIEW_RULES.md Section 21.1
```

### SUGGESTION Issue Template

```
[SUGGESTION] Consider using data provider for multiple test scenarios

File: tests/Unit/Domain/Order/OrderTest.php:45

Multiple similar test methods could be consolidated into a single
data-provider-driven test for better maintainability.

Reference: PR_REVIEW_RULES.md Section 9.2
```

---

## Review Output Template

```markdown
## PR Review: #{pr_number} - {title}

**Reviewer:** AI Code Review
**Date:** {date}
**Score:** {score}/100

---

### Issues Found

| Severity | Count |
|----------|-------|
| CRITICAL | {count} |
| WARNING  | {count} |
| SUGGESTION | {count} |

---

### Critical Issues

1. **{issue_title}**
   - File: {file_path}:{line}
   - Finding: {description}
   - Fix: {fix_suggestion}
   - Reference: {section}

---

### Good Parts

- {positive_aspect_1}
- {positive_aspect_2}

---

### Merge Status

{status_emoji} {Ready to merge | Can merge after warnings fixed | Cannot merge}

**Must fix:**
1. {critical_issue}

**Should fix:**
1. {warning_issue}

**Nice to have:**
1. {suggestion}
```

---

## Auto-Reject Criteria

A PR MUST be rejected if any of the following are found:

- Controller directly accessing database (Eloquent/DB facade)
- Domain class importing from `Illuminate\` namespace
- `float` used for currency amounts
- Business logic in controller
- Missing `DB::transaction()` for multi-write operations
- Domain events dispatched inside transaction without `afterCommit`
- SQL injection vulnerability (raw string interpolation)
- Mass assignment vulnerability (`$request->all()`)
- Missing authorization check on endpoint
- Secrets/credentials committed
- Action importing concrete repository implementation

---

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-03-17 | Initial comprehensive Laravel PR review rules. 23 sections covering architecture, domain, repository, actions, errors, style, money, testing, security, transactions, events, anti-patterns, observability, performance, CQRS, and deployment. Adapted from Go Clean Architecture rules for the Laravel/PHP ecosystem. |

---

**Maintainer:** Engineering Team
**Documentation Source:** Adapted from Go backend PR review rules for Laravel ecosystem
