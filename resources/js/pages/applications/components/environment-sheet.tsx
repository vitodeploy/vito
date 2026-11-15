import { router, usePage } from '@inertiajs/react';
import { DialogControlProps } from '@/components/dialogs/registry';
import { EnvironmentPage } from '@/pages/applications/environments/show';
import { panelProps } from './canvas';
import DeploymentsSheet from './deployments-sheet';
import SettingsSheet from './settings-sheet';
import VariablesSheet from './variables-sheet';

export default function EnvironmentSheet({ open, onOpenChange, returnToList }: DialogControlProps & { returnToList: boolean }) {
  const props = usePage<EnvironmentPage>().props;
  const { application, environment, component, resource, can } = props;
  const selection = component ? `?component=${component.id}` : resource ? `?resource=${resource.id}` : '';
  const onClose = () => {
    router.visit(`/applications/${application.id}/environments/${environment.id}${selection}`, {
      preserveState: true,
      preserveScroll: true,
      only: panelProps,
      onSuccess: () => onOpenChange(false),
    });
  };

  if (props.sheet === 'settings') {
    return (
      <SettingsSheet
        application={application}
        environment={environment}
        scripts={props.scripts}
        primaryCandidates={props.primaryCandidates}
        sourceControls={props.sourceControls}
        deploymentActive={props.deploymentActive}
        can={can}
        open={open}
        onClose={onClose}
      />
    );
  }

  if (props.sheet === 'variables') {
    return (
      <VariablesSheet
        application={application}
        environment={environment}
        variables={props.variables}
        warnings={props.warnings}
        canUpdate={can.update}
        open={open}
        onClose={onClose}
      />
    );
  }

  if (props.sheet === 'deployments' && props.deployments) {
    return (
      <DeploymentsSheet
        application={application}
        environment={environment}
        deployments={props.deployments}
        deployment={props.deployment}
        open={open}
        returnToList={returnToList}
        onClose={onClose}
      />
    );
  }

  return null;
}
