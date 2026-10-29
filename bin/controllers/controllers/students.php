<?php

namespace Epaphrodites\controllers\controllers;

use DateTimeImmutable;
use Epaphrodites\controllers\switchers\MainSwitchers;
use Epaphrodites\services\StudentsService;

final class students extends MainSwitchers
{
    private StudentsService $studentsService;

    public function __construct()
    {
        $this->studentsService = new StudentsService();
    }

    public final function manageStudents(string $html): void
    {
        $archived = ($_GET['archived'] ?? '0') === '1';
        $pageValue = filter_var($_GET['_p'] ?? 1, FILTER_VALIDATE_INT);
        $currentPage = is_int($pageValue) && $pageValue > 0 ? $pageValue : 1;
        $formValues = $this->emptyStudentForm();
        $editingId = null;
        $message = '';
        $alert = '';

        if (isset($_POST['archived']) && is_string($_POST['archived'])) {
            $archived = $_POST['archived'] === '1';
        }

        if (static::isValidMethod(true)) {
            $action = isset($_POST['action']) && is_string($_POST['action'])
                ? trim($_POST['action'])
                : '';

            if ($action === 'save') {
                $formValues = $this->readStudentForm();
                $rawId = $_POST['student_id'] ?? '';
                $editingId = $rawId === '' ? null : $this->positiveId($rawId);

                if ($rawId !== '' && $editingId === null) {
                    $message = 'Invalid student identifier.';
                    $alert = 'alert-danger';
                } else {
                    $validation = $this->validateStudentForm($formValues);

                    if ($validation['error'] !== '') {
                        $message = $validation['error'];
                        $alert = 'alert-danger';
                    } elseif (
                        $editingId !== null
                        && $this->studentsService->getStudent($editingId) === null
                    ) {
                        $message = 'The student could not be found.';
                        $alert = 'alert-danger';
                    } elseif (!$this->studentsService->classExists((int) $validation['data']['classe_id'])) {
                        $message = 'Select an existing class.';
                        $alert = 'alert-danger';
                    } elseif (
                        !$this->studentsService->isMatriculeAvailable(
                            (string) $validation['data']['matricule'],
                            $editingId
                        )
                    ) {
                        $message = 'This matricule is already in use.';
                        $alert = 'alert-danger';
                    } elseif ($this->studentsService->saveStudent($validation['data'], $editingId)) {
                        $message = $editingId === null
                            ? 'Student registered successfully.'
                            : 'Student updated successfully.';
                        $alert = 'alert-success';
                        $formValues = $this->emptyStudentForm();
                        $editingId = null;
                    } else {
                        $message = 'The student could not be saved.';
                        $alert = 'alert-danger';
                    }
                }
            } elseif ($action === 'archive' || $action === 'restore') {
                $studentId = $this->positiveId($_POST['student_id'] ?? null);
                $student = $studentId === null
                    ? null
                    : $this->studentsService->getStudent($studentId);

                if ($student === null) {
                    $message = 'The student could not be found.';
                    $alert = 'alert-danger';
                } elseif (
                    !$this->studentsService->setStudentArchived(
                        $studentId,
                        $action === 'archive'
                    )
                ) {
                    $message = 'The student status could not be updated.';
                    $alert = 'alert-danger';
                } else {
                    $message = $action === 'archive'
                        ? 'Student archived successfully.'
                        : 'Student restored successfully.';
                    $alert = 'alert-success';
                }
            } else {
                $message = 'Invalid student action.';
                $alert = 'alert-danger';
            }
        } elseif (array_key_exists('id', $_GET)) {
            $requestedId = $this->positiveId($_GET['id']);
            $student = $requestedId === null
                ? null
                : $this->studentsService->getStudent($requestedId);

            if ($student === null) {
                $message = 'The student could not be found.';
                $alert = 'alert-danger';
            } else {
                $editingId = $requestedId;
                $archived = (int) ($student['est_archive'] ?? 0) === 1;

                foreach ($formValues as $field => $value) {
                    $formValues[$field] = (string) ($student[$field] ?? '');
                }
            }
        }

        $pageSize = 50;
        $total = $this->studentsService->countStudents($archived);
        $totalPages = max(1, (int) ceil($total / $pageSize));
        $currentPage = min($currentPage, $totalPages);
        $students = $this->studentsService->listStudents(
            $archived,
            ($currentPage - 1) * $pageSize,
            $pageSize
        );
        $classes = $this->studentsService->getClasses();
        $classNames = [];

        foreach ($classes as $class) {
            if (isset($class['id'], $class['nom'])) {
                $classNames[(string) $class['id']] = (string) $class['nom'];
            }
        }

        $this->views($html, [
            'students' => $students,
            'classes' => $classes,
            'class_names' => $classNames,
            'form_values' => $formValues,
            'editing_id' => $editingId,
            'archived' => $archived,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total' => $total,
            'reponse' => $message,
            'alert' => $alert,
        ], true);
    }

    private function emptyStudentForm(): array
    {
        return [
            'matricule' => '',
            'nom' => '',
            'prenom' => '',
            'date_naissance' => '',
            'sexe' => '',
            'classe_id' => '',
            'telephone' => '',
            'email' => '',
            'adresse' => '',
        ];
    }

    private function readStudentForm(): array
    {
        $values = [];

        foreach (array_keys($this->emptyStudentForm()) as $field) {
            $value = $_POST[$field] ?? '';
            $values[$field] = is_string($value) ? trim($value) : '';
        }

        return $values;
    }

    /**
     * @return array{data: array<string, int|string|null>|null, error: string}
     */
    private function validateStudentForm(array $values): array
    {
        foreach (['matricule', 'nom', 'prenom', 'classe_id'] as $requiredField) {
            if ($values[$requiredField] === '') {
                return ['data' => null, 'error' => 'Matricule, last name, first name and class are required.'];
            }
        }

        $maxLengths = [
            'matricule' => 30,
            'nom' => 100,
            'prenom' => 100,
            'date_naissance' => 10,
            'sexe' => 20,
            'classe_id' => 10,
            'telephone' => 30,
            'email' => 254,
            'adresse' => 2000,
        ];

        foreach ($maxLengths as $field => $maxLength) {
            if (
                !mb_check_encoding($values[$field], 'UTF-8')
                || mb_strlen($values[$field], 'UTF-8') > $maxLength
            ) {
                return ['data' => null, 'error' => 'One or more student fields are invalid or too long.'];
            }
        }

        $classId = filter_var($values['classe_id'], FILTER_VALIDATE_INT);

        if (!is_int($classId) || $classId < 1) {
            return ['data' => null, 'error' => 'Select a valid class.'];
        }

        $dateOfBirth = null;

        if ($values['date_naissance'] !== '') {
            $dateOfBirth = DateTimeImmutable::createFromFormat('!Y-m-d', $values['date_naissance']);
            $dateErrors = DateTimeImmutable::getLastErrors();

            if (
                $dateOfBirth === false
                || $dateOfBirth->format('Y-m-d') !== $values['date_naissance']
                || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            ) {
                return ['data' => null, 'error' => 'Enter the date of birth in YYYY-MM-DD format.'];
            }
        }

        if ($values['email'] !== '' && filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            return ['data' => null, 'error' => 'Enter a valid email address.'];
        }

        return [
            'data' => [
                'matricule' => $values['matricule'],
                'nom' => $values['nom'],
                'prenom' => $values['prenom'],
                'date_naissance' => $values['date_naissance'] !== '' ? $values['date_naissance'] : null,
                'sexe' => $values['sexe'] !== '' ? $values['sexe'] : null,
                'classe_id' => $classId,
                'telephone' => $values['telephone'] !== '' ? $values['telephone'] : null,
                'email' => $values['email'] !== '' ? $values['email'] : null,
                'adresse' => $values['adresse'] !== '' ? $values['adresse'] : null,
            ],
            'error' => '',
        ];
    }

    private function positiveId(mixed $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($id) && $id > 0 ? $id : null;
    }
}
