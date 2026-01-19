<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "It's A Wrap POS API",
    description: "API for It's A Wrap POS system - supports Flutter mobile apps and web applications",
    contact: new OA\Contact(name: "API Support", email: "support@itsawrap.com")
)]
#[OA\Server(url: "/api", description: "API Server")]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Enter your Bearer token"
)]
#[OA\Tag(name: "Authentication", description: "Auth endpoints")]
#[OA\Tag(name: "Users", description: "User management")]
#[OA\Tag(name: "Categories", description: "Menu categories")]
#[OA\Tag(name: "Items", description: "Menu items")]
#[OA\Tag(name: "Options", description: "Item options and values")]
#[OA\Tag(name: "Orders", description: "Order management")]
#[OA\Tag(name: "Payments", description: "Payment processing")]
#[OA\Tag(name: "Sessions", description: "Cash register sessions")]
#[OA\Tag(name: "Customers", description: "Customer management")]
#[OA\Tag(name: "Settings", description: "System settings")]
#[OA\Tag(name: "Branches", description: "Branch management")]
abstract class Controller
{
    //
}
