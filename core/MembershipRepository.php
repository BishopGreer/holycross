<?php

declare(strict_types=1);

final class MembershipRepository
{
    public function create(array $form, array $members): int
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO membership_applications (
                    household_name, primary_name, preferred_name, pronouns, gender_identity,
                    date_of_birth, email, phone, address_line1, address_line2, city, state,
                    postal_code, preferred_contact, current_church, baptism_status,
                    sacraments_received, ministries_interest, pastoral_notes,
                    accessibility_needs, consent_to_contact, status, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "new", NOW(), NOW()
                )'
            );
            $stmt->execute([
                $form['household_name'],
                $form['primary_name'],
                $form['preferred_name'],
                $form['pronouns'],
                $form['gender_identity'],
                $form['date_of_birth'] ?: null,
                $form['email'],
                $form['phone'],
                $form['address_line1'],
                $form['address_line2'],
                $form['city'],
                $form['state'],
                $form['postal_code'],
                $form['preferred_contact'],
                $form['current_church'],
                $form['baptism_status'],
                $form['sacraments_received'],
                $form['ministries_interest'],
                $form['pastoral_notes'],
                $form['accessibility_needs'],
                $form['consent_to_contact'] ? 1 : 0,
            ]);
            $applicationId = (int)$pdo->lastInsertId();

            $memberStmt = $pdo->prepare(
                'INSERT INTO membership_family_members (
                    application_id, name, preferred_name, pronouns, gender_identity,
                    date_of_birth, relationship, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );

            foreach ($members as $member) {
                $memberStmt->execute([
                    $applicationId,
                    $member['name'],
                    $member['preferred_name'],
                    $member['pronouns'],
                    $member['gender_identity'],
                    $member['date_of_birth'] ?: null,
                    $member['relationship'],
                    $member['notes'],
                ]);
            }

            $pdo->commit();
            return $applicationId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function all(): array
    {
        $stmt = Database::connect()->query(
            'SELECT id, household_name, primary_name, preferred_name, email, phone, status, created_at
             FROM membership_applications
             ORDER BY created_at DESC'
        );

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM membership_applications WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $application = $stmt->fetch();

        if (!$application) {
            return null;
        }

        $application['members'] = $this->members($id);
        return $application;
    }

    public function updateStatus(int $id, string $status): void
    {
        if (!in_array($status, ['new', 'reviewed', 'contacted', 'archived'], true)) {
            throw new InvalidArgumentException('Invalid membership status.');
        }

        $stmt = Database::connect()->prepare('UPDATE membership_applications SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    private function members(int $applicationId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT * FROM membership_family_members WHERE application_id = ? ORDER BY id ASC'
        );
        $stmt->execute([$applicationId]);

        return $stmt->fetchAll();
    }
}
