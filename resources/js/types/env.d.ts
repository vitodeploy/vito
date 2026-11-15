export interface EnvVariable {
  id: string;
  key: string;
  value: string;
  isSecret: boolean;
  isNew?: boolean;
}
