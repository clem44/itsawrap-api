<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SliderButton',
    required: ['label', 'url', 'style', 'sort_order'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'sort_order', type: 'integer', example: 0),
        new OA\Property(property: 'label', type: 'string', example: 'Order Now'),
        new OA\Property(property: 'url', type: 'string', example: '/order'),
        new OA\Property(property: 'style', type: 'string', enum: ['solid', 'outline'], example: 'solid'),
    ]
)]
#[OA\Schema(
    schema: 'Slide',
    required: ['id', 'sort_order', 'heading', 'text_alignment', 'overlay_color', 'overlay_opacity', 'transition', 'transition_ms', 'buttons'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'sort_order', type: 'integer', example: 0),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'badge', type: 'string', nullable: true, example: 'Featured Special'),
        new OA\Property(property: 'heading', type: 'string', example: 'Taste the Authentic Wrap'),
        new OA\Property(property: 'body', type: 'string', nullable: true),
        new OA\Property(property: 'text_alignment', type: 'string', enum: ['left', 'center', 'right']),
        new OA\Property(property: 'overlay_color', type: 'string', example: '#000000'),
        new OA\Property(property: 'overlay_opacity', type: 'integer', example: 45),
        new OA\Property(property: 'transition', type: 'string', enum: ['fade', 'slide-left', 'slide-right']),
        new OA\Property(property: 'transition_ms', type: 'integer', example: 500),
        new OA\Property(property: 'media', ref: '#/components/schemas/PrimaryMedia', nullable: true),
        new OA\Property(property: 'buttons', type: 'array', items: new OA\Items(ref: '#/components/schemas/SliderButton')),
    ]
)]
#[OA\Schema(
    schema: 'Slider',
    required: ['id', 'name', 'slug', 'is_active', 'autoplay', 'autoplay_interval_ms', 'slides'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string', example: 'Homepage Main Carousel'),
        new OA\Property(property: 'slug', type: 'string', example: 'homepage-main'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'autoplay', type: 'boolean', example: true),
        new OA\Property(property: 'autoplay_interval_ms', type: 'integer', example: 5000),
        new OA\Property(property: 'slides', type: 'array', items: new OA\Items(ref: '#/components/schemas/Slide')),
    ]
)]
class SliderSchemas {}
