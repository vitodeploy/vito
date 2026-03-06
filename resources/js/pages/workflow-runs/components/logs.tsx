import LogOutput from '@/components/log-output';
import { WorkflowRun } from '@/types/workflow-run';
import { useCallback, useEffect, useRef, useState } from 'react';
import axios from 'axios';
import { useSocketListener } from '@/hooks/use-socket-events';

export default function Logs({ workflowRun }: { workflowRun: WorkflowRun }) {
  const [content, setContent] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const runIdRef = useRef(workflowRun.id);
  runIdRef.current = workflowRun.id;

  // Fetch initial content
  useEffect(() => {
    setIsLoading(true);
    setError(null);
    setContent('');

    axios
      .get(route('workflow-runs.log', { workflow: workflowRun.workflow_id, workflowRun: workflowRun.id }))
      .then((response) => {
        const data = typeof response.data === 'string' ? response.data : JSON.stringify(response.data, null, 2);
        setContent(data);
      })
      .catch((err: unknown) => {
        if (axios.isAxiosError(err)) {
          setError(err.response?.data?.error || 'An error occurred while fetching the log');
        } else {
          setError('Unknown error occurred');
        }
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, [workflowRun.id, workflowRun.workflow_id]);

  // Listen for realtime log content appends
  useSocketListener(
    useCallback((event) => {
      if (event.type !== 'workflow-run.log-content') return;
      if (!event.data || (event.data as { id?: number }).id !== runIdRef.current) return;

      const buf = (event.data as { content?: string }).content;
      if (buf) {
        setContent((prev) => prev + buf);
      }
    }, []),
  );

  return (
    <LogOutput className="rounded-lg border shadow">
      <>
        {isLoading && 'Loading...'}
        {error && <div className="text-red-500">Error: {error}</div>}
        {content && !error && content}
      </>
    </LogOutput>
  );
}
