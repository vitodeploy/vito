import { ReactNode } from 'react';

export default function HeaderContainer({ children }: { children: ReactNode }) {
  return <div className="flex items-center justify-between">{children}</div>;
}
