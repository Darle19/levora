<?php

namespace App\Observers;

use App\Models\TourTemplateLeg;

/**
 * Keep round_trip_pair_id symmetric.
 *
 * The admin form asks ops to set "Pairs with leg" on one side of a pair
 * (e.g. leg 4 → leg 1). The FlightPathGenerator reconstructs the
 * bidirectional relation anyway, but persisting both sides avoids subtle
 * bugs in other code that walks only one direction of the link.
 */
class TourTemplateLegObserver
{
    public function saved(TourTemplateLeg $leg): void
    {
        if (! $leg->wasChanged('round_trip_pair_id')) {
            return;
        }

        // Clear the previous peer (if any) that no longer points back at us.
        $previousPairId = $leg->getOriginal('round_trip_pair_id');
        if ($previousPairId && $previousPairId !== $leg->round_trip_pair_id) {
            $prev = TourTemplateLeg::find($previousPairId);
            if ($prev && $prev->round_trip_pair_id === $leg->id) {
                $prev->updateQuietly(['round_trip_pair_id' => null]);
            }
        }

        // Mirror onto the new peer.
        if ($leg->round_trip_pair_id) {
            $peer = TourTemplateLeg::find($leg->round_trip_pair_id);
            if ($peer && $peer->round_trip_pair_id !== $leg->id) {
                $peer->updateQuietly(['round_trip_pair_id' => $leg->id]);
            }
        }
    }

    public function deleting(TourTemplateLeg $leg): void
    {
        if ($leg->round_trip_pair_id) {
            $peer = TourTemplateLeg::find($leg->round_trip_pair_id);
            if ($peer && $peer->round_trip_pair_id === $leg->id) {
                $peer->updateQuietly(['round_trip_pair_id' => null]);
            }
        }
    }
}
