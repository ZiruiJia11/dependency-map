/** Values mirror App\Enums\NodeType for type-safe form options and rendering. */
export type NodeType =
    | 'task'
    | 'person'
    | 'service'
    | 'database'
    | 'supplier'
    | 'approval'
    | 'other';

/** Values mirror App\Enums\NodeStatus. */
export type NodeStatus = 'normal' | 'at_risk' | 'blocked' | 'completed';

/** Serialized Node shape returned by the Project show page. */
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

/** A directed source-to-target connection between two project nodes. */
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

/** Project page payload, with relationships optional outside the show page. */
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
