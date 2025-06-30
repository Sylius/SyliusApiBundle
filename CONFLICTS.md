# CONFLICTS

This document explains why certain conflicts were added to `composer.json` and references related issues.

- `symfony/serializer:^6.4.23`:

  This version introduces a change in method signature that is not compatible with API Platform 2.7 and leads to a fatal error:
  `PHP Fatal error:  Declaration of ApiPlatform\Serializer\AbstractItemNormalizer::getAllowedAttributes($classOrObject, array $context, $attributesAsString = false) must be compatible with Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer::getAllowedAttributes(object|string $classOrObject, array $context, bool $attributesAsString = false): array|bool in /home/runner/work/Sylius/Sylius/vendor/api-platform/core/src/Serializer/AbstractItemNormalizer.php on line 493`
