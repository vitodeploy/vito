export type Service = {
  type: string;
  name: string;
  version: string;
};

export interface ServerTemplate {
  id: number;
  name: string;
  services: Service[];
}
