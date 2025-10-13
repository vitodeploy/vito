import LogOutput from '@/components/log-output';
import { WorkflowRun } from '@/types/workflow-run';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';

export default function Logs({ workflowRun }: { workflowRun: WorkflowRun }) {
  const query = useQuery({
    queryKey: ['workflow-run-logs', workflowRun.id],
    queryFn: async () => {
      try {
        const response = await axios.get(route('workflow-runs.log', { workflow: workflowRun.workflow_id, workflowRun: workflowRun.id }));
        return response.data;
      } catch (error: unknown) {
        if (axios.isAxiosError(error)) {
          throw new Error(error.response?.data?.error || 'An error occurred while fetching the log');
        }
        throw new Error('Unknown error occurred');
      }
    },
    enabled: true,
    retry: false,
    refetchInterval: (query) => {
      if (query.state.status === 'error') return false;
      if (workflowRun.status !== 'running') return false;
      return 2500;
    },
  });

  return (
    <LogOutput className="rounded-lg border shadow">
      <>
        {query.isLoading && 'Loading...'}
        {query.isError && <div className="text-red-500">Error: {query.error.message}</div>}
        {query.data && !query.isError && query.data}
      </>
    </LogOutput>
  );
}
