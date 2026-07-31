export type NodeType =
    | 'task'
    | 'person'
    | 'service'
    | 'database'
    | 'supplier'
    | 'approval'
    | 'other';

export type NodeStatus = 'normal' | 'at_risk' | 'blocked' | 'complete';

export interface Node {
    id: number;
    project_id: number;
    name: string;
    description: string | null;
    type: NodeType;
    status: NodeStatus;
    position_x: string | null;
    position_y: string | null;
    created_at: string;
    updated_at: string;
}

export interface Dependency {
    id: number;
    project_id: number;
    source_node_id: number;
    target_node_id: number;
    description: string | null;
    source_node: Node;
    target_node: Node;
    created_at: string;
    updated_at: string;
}

export interface Project {
    id: number;
    name: string;
    description: string | null;
    nodes_count?: number;
    dependencies_count?: number;
    nodes?: Node[];
    dependencies?: Dependency[];
    created_at: string;
    updated_at: string;
}
