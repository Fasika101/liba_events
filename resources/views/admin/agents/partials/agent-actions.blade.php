<form method="POST" class="d-inline mr-1 mb-1 mb-md-0"
      action="{{ route('admin.agents.suspend', $agent) }}"
      onsubmit="return confirm('{{ $agent->is_suspended ? 'Reactivate this agent?' : 'Suspend this agent? They will not be able to log in.' }}')">
    @csrf
    @if($agent->is_suspended)
        <button type="submit"
                class="btn btn-sm btn-success"
                title="Reactivate agent"
                style="min-width:88px;">
            <i class="fas fa-user-check mr-1"></i> Activate
        </button>
    @else
        <button type="submit"
                class="btn btn-sm btn-danger"
                title="Suspend agent"
                style="min-width:88px;">
            <i class="fas fa-user-slash mr-1"></i> Suspend
        </button>
    @endif
</form>
@if(($agent->tickets_count ?? 0) > 0)
    <button type="button" class="btn btn-sm btn-outline-secondary" disabled
            title="Cannot delete: this agent has ticket sales. The selling agent cannot be removed from those records. Use Suspend instead.">
        <i class="fas fa-trash-alt"></i>
    </button>
@else
    <form class="d-inline ml-1 mb-1 mb-md-0" action="{{ route('admin.agents.destroy', $agent) }}" method="POST"
          onsubmit="return confirm('Delete this agent? They have no ticket sales on record.');">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete agent (only if no ticket sales)">
            <i class="fas fa-trash-alt"></i>
        </button>
    </form>
@endif
