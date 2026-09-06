<?php

namespace App\Http\Requests\Api\Concerns;

use Illuminate\Validation\Validator;

trait ValidatesOrderParticipants
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function participantRules(): array
    {
        return [
            'participants' => ['nullable', 'array', 'min:1', 'max:25'],
            'participants.*.client_id' => ['required_with:participants', 'string', 'max:100', 'distinct'],
            'participants.*.name' => ['required_with:participants', 'string', 'max:255'],
            'participants.*.is_primary' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function itemParticipantRules(): array
    {
        return [
            'items.*.participant_client_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function bundleParticipantRules(): array
    {
        return [
            'bundles.*.participant_client_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function validateParticipantAssignments(Validator $validator): void
    {
        $participants = $this->input('participants', []);
        $hasParticipants = is_array($participants) && count($participants) > 0;

        if (! $hasParticipants) {
            return;
        }

        $participantClientIds = collect($participants)
            ->pluck('client_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $primaryCount = collect($participants)
            ->filter(fn ($participant) => filter_var($participant['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN))
            ->count();

        if ($primaryCount > 1) {
            $validator->errors()->add('participants', 'Only one participant may be marked as primary.');
        }

        foreach ($this->input('items', []) as $index => $item) {
            $participantClientId = $item['participant_client_id'] ?? null;

            if (blank($participantClientId)) {
                $validator->errors()->add("items.$index.participant_client_id", 'Each item must identify the participant it belongs to.');
            } elseif (! in_array((string) $participantClientId, $participantClientIds, true)) {
                $validator->errors()->add("items.$index.participant_client_id", 'The selected participant is invalid.');
            }
        }

        foreach ($this->input('bundles', []) as $index => $bundle) {
            $participantClientId = $bundle['participant_client_id'] ?? null;

            if (blank($participantClientId)) {
                $validator->errors()->add("bundles.$index.participant_client_id", 'Each bundle must identify the participant it belongs to.');
            } elseif (! in_array((string) $participantClientId, $participantClientIds, true)) {
                $validator->errors()->add("bundles.$index.participant_client_id", 'The selected participant is invalid.');
            }
        }
    }
}
