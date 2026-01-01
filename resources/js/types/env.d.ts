export interface EnvVariable {
  key: string;
  value: string;
  isSecret: boolean;
  isNew?: boolean; // True for variables being created in this session
}
