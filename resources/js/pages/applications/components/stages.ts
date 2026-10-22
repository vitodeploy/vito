export const stages = [
  { id: 'build', label: 'Build', description: 'Runs on every component before the release goes live.' },
  { id: 'pre-release', label: 'Release command', description: 'Runs once on the primary component, e.g. migrations.' },
  { id: 'post-release', label: 'Post-release', description: 'Runs on every component after the switch. Failures do not roll back.' },
];
