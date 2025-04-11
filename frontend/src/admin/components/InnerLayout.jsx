import React, { Component } from "react";
import { Row, Col } from "antd";
import { motion } from "framer-motion";
import styles from "../styles/DataManagementPage.module.scss";

class InnerLayout extends Component {
    render() {
        const { title, subtitle, child } = this.props;

        return (
            <div className={styles.pageContainer}>
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.5 }}
                >
                    <div className={styles.headerSection}>
                        <h1 className={styles.title}>{title}</h1>
                        <p className={styles.subtitle}>{subtitle}</p>
                    </div>
                    <Row gutter={[16, 16]}>
                        <Col xs={24}></Col>
                    </Row>
                    {child}
                </motion.div>
            </div>
        );
    }
}

export default InnerLayout;
