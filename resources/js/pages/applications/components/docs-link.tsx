export default function DocsLink({ page = 'concepts' }: { page?: string }) {
  return <a className="text-muted-foreground hover:text-foreground text-sm underline underline-offset-4" href={`https://vitodeploy.com/docs/4.x/applications/${page}`} target="_blank" rel="noreferrer">Read the Applications guide</a>;
}
