<?php

namespace CineVerse\Models;

/**
 * User Model
 * 
 * Represents a user in the CineVerse application
 */
class User
{
    private array $attributes;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get user ID
     */
    public function getId(): int
    {
        return (int) $this->attributes['id'];
    }

    /**
     * Get user UUID
     */
    public function getUuid(): string
    {
        return $this->attributes['uuid'];
    }

    /**
     * Get username
     */
    public function getUsername(): string
    {
        return $this->attributes['username'];
    }

    /**
     * Get email
     */
    public function getEmail(): string
    {
        return $this->attributes['email'];
    }

    /**
     * Get full name
     */
    public function getFullName(): string
    {
        return trim($this->attributes['first_name'] . ' ' . $this->attributes['last_name']);
    }

    /**
     * Get first name
     */
    public function getFirstName(): string
    {
        return $this->attributes['first_name'];
    }

    /**
     * Get last name
     */
    public function getLastName(): string
    {
        return $this->attributes['last_name'];
    }

    /**
     * Get phone number
     */
    public function getPhone(): ?string
    {
        return $this->attributes['phone'] ?? null;
    }

    /**
     * Get avatar URL
     */
    public function getAvatar(): ?string
    {
        return $this->attributes['avatar'] ?? null;
    }

    /**
     * Get date of birth
     */
    public function getDateOfBirth(): ?string
    {
        return $this->attributes['date_of_birth'] ?? null;
    }

    /**
     * Get gender
     */
    public function getGender(): ?string
    {
        return $this->attributes['gender'] ?? null;
    }

    /**
     * Get country
     */
    public function getCountry(): string
    {
        return $this->attributes['country'] ?? 'RW';
    }

    /**
     * Get language
     */
    public function getLanguage(): string
    {
        return $this->attributes['language'] ?? 'en';
    }

    /**
     * Get user role
     */
    public function getRole(): string
    {
        return $this->attributes['role'] ?? 'user';
    }

    /**
     * Get user status
     */
    public function getStatus(): string
    {
        return $this->attributes['status'] ?? 'active';
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->getRole() === 'admin';
    }

    /**
     * Check if user is moderator
     */
    public function isModerator(): bool
    {
        return in_array($this->getRole(), ['admin', 'moderator']);
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->getStatus() === 'active';
    }

    /**
     * Check if email is verified
     */
    public function isEmailVerified(): bool
    {
        return !empty($this->attributes['email_verified_at']);
    }

    /**
     * Get last login timestamp
     */
    public function getLastLoginAt(): ?string
    {
        return $this->attributes['last_login_at'] ?? null;
    }

    /**
     * Get last login IP
     */
    public function getLastLoginIp(): ?string
    {
        return $this->attributes['last_login_ip'] ?? null;
    }

    /**
     * Get created at timestamp
     */
    public function getCreatedAt(): string
    {
        return $this->attributes['created_at'];
    }

    /**
     * Get updated at timestamp
     */
    public function getUpdatedAt(): string
    {
        return $this->attributes['updated_at'];
    }

    /**
     * Get attribute value
     */
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Set attribute value
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get all attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Convert to array (excluding sensitive data)
     */
    public function toArray(): array
    {
        $data = $this->attributes;
        unset($data['password'], $data['remember_token']);
        return $data;
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
